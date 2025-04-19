<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Persistence\Eloquent;

use Core\BoundedContext\SubaccountManagement\Domain\Entities\Subaccount;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
use Core\BoundedContext\SubaccountManagement\Domain\ValueObjects\SubaccountConfig;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Persistence\Eloquent\Models\SubaccountModel;
use DateTime;

final class EloquentSubaccountRepository implements SubaccountRepositoryInterface
{
    public function findByKey(string $key): ?Subaccount
    {
        $model = SubaccountModel::where('key', $key)->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    public function findById(string $id): ?Subaccount
    {
        $model = SubaccountModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->mapToDomain($model);
    }

    public function save(Subaccount $subaccount): void
    {
        $model = SubaccountModel::firstOrNew(['id' => $subaccount->id()]);

        $model->id = $subaccount->id();
        $model->key = $subaccount->key();
        $model->name = $subaccount->name();
        $model->connection = $subaccount->config()->connection();
        $model->tables = json_encode($subaccount->config()->tables());

        $model->save();
    }

    public function findAll(): array
    {
        return SubaccountModel::all()
            ->map(fn($model) => $this->mapToDomain($model))
            ->toArray();
    }

    private function mapToDomain(SubaccountModel $model): Subaccount
    {
        return new Subaccount(
            $model->id,
            $model->key,
            $model->name,
            new SubaccountConfig(
                $model->connection,
                json_decode($model->tables, true)
            ),
            new DateTime($model->created_at),
            new DateTime($model->updated_at)
        );
    }
}
