<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->dropIndexIfExists('global_rate_buckets', 'grb_active_window_idx');
        $this->dropIndexIfExists('global_rate_buckets', 'grb_priority_active_idx');
        $this->dropIndexIfExists('user_rate_buckets', 'urb_effective_window_idx');

        $this->dropColumnsIfExist('global_rate_buckets', [
            'priority',
            'effective_from',
            'effective_to',
        ]);

        $this->dropColumnsIfExist('user_rate_buckets', [
            'priority',
            'effective_from',
            'effective_to',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_rate_buckets', function (Blueprint $table): void {
            if (! Schema::hasColumn('global_rate_buckets', 'priority')) {
                $table->unsignedSmallInteger('priority')->default(100);
            }

            if (! Schema::hasColumn('global_rate_buckets', 'effective_from')) {
                $table->timestamp('effective_from')->nullable();
            }

            if (! Schema::hasColumn('global_rate_buckets', 'effective_to')) {
                $table->timestamp('effective_to')->nullable();
            }
        });

        Schema::table('user_rate_buckets', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_rate_buckets', 'priority')) {
                $table->unsignedSmallInteger('priority')->default(100);
            }

            if (! Schema::hasColumn('user_rate_buckets', 'effective_from')) {
                $table->timestamp('effective_from')->nullable();
            }

            if (! Schema::hasColumn('user_rate_buckets', 'effective_to')) {
                $table->timestamp('effective_to')->nullable();
            }
        });

        $this->createIndexIfMissing(
            table: 'global_rate_buckets',
            indexName: 'grb_active_window_idx',
            columns: ['is_active', 'effective_from', 'effective_to'],
        );
        $this->createIndexIfMissing(
            table: 'global_rate_buckets',
            indexName: 'grb_priority_active_idx',
            columns: ['priority', 'is_active'],
        );
        $this->createIndexIfMissing(
            table: 'user_rate_buckets',
            indexName: 'urb_effective_window_idx',
            columns: ['effective_from', 'effective_to'],
        );
    }

    private function dropColumnsIfExist(string $table, array $columns): void
    {
        $existingColumns = array_values(array_filter(
            $columns,
            static fn (string $column): bool => Schema::hasColumn($table, $column),
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($existingColumns): void {
            $blueprint->dropColumn($existingColumns);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    private function createIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $result = DB::table('information_schema.statistics')
                ->selectRaw('COUNT(*) AS aggregate')
                ->where('table_schema', $database)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->value('aggregate');

            return (int) $result > 0;
        }

        return false;
    }
};
