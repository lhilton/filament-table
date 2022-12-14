<?php

namespace Filament\Tables\Concerns;

use Filament\Tables\Contracts\HasRelationshipTable;
use Filament\Tables\Events\TableSorted;
use Filament\Tables\Events\TableSorting;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait CanReorderRecords
{
    public bool $isTableReordering = false;

    public function reorderTable(array $order): void
    {
        if (! $this->isTableReorderable()) {
            return;
        }

        event(new TableSorting($this->getOwnerRecord()));

        $orderColumn = $this->getTableReorderColumn();

        if (
            $this instanceof HasRelationshipTable &&
            (($relationship = $this->getRelationship()) instanceof BelongsToMany) &&
            in_array($orderColumn, $relationship->getPivotColumns())
        ) {
            foreach ($order as $index => $recordKey) {
                $this->getTableRecord($recordKey)->{$relationship->getPivotAccessor()}->update([
                    $orderColumn => $index + 1,
                ]);
            }

            event(new TableSorted($this->getOwnerRecord()));

            return;
        }

        foreach ($order as $index => $recordKey) {
            $this->getTableRecord($recordKey)->update([
                $orderColumn => $index + 1,
            ]);
        }

        event(new TableSorted($this->ownerRecord));
    }

    public function toggleTableReordering(): void
    {
        $this->isTableReordering = ! $this->isTableReordering;
    }

    public function isTableReordering(): bool
    {
        return $this->isTableReorderable() && $this->isTableReordering;
    }

    protected function isTablePaginationEnabledWhileReordering(): bool
    {
        return false;
    }

    protected function isTableReorderable(): bool
    {
        return filled($this->getTableReorderColumn());
    }

    protected function getTableReorderColumn(): ?string
    {
        return null;
    }
}
