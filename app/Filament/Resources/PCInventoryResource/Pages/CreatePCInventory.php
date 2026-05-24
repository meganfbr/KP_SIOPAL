<?php

namespace App\Filament\Resources\PCInventoryResource\Pages;

use App\Filament\Resources\PCInventoryResource;
use App\Models\PCDetail;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePCInventory extends CreateRecord
{
    protected static string $resource = PCInventoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ambil lab ID dari URL parameter jika ada
        $labId = request()->get('tableFilters')['laboratorium']['value'] ?? null;

        if ($labId) {
            $data['laboratorium_id'] = $labId;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Ambil data detail dari form
        $detailsData = $data['details'];
        unset($data['details']); // Hapus dari data utama

        // 2. Buat record di tabel pc_details
        $pcDetail = PCDetail::create($detailsData);

        // 3. Kaitkan record pc_details ke data inventaris utama
        $data['inventoriable_id'] = $pcDetail->id;
        $data['inventoriable_type'] = PCDetail::class;

        // Sync lokasi_id and petugas_id
        $data['lokasi_id'] = $data['laboratorium_id'];
        if (auth()->check()) {
            $data['petugas_id'] = auth()->id();
        }

        // 4. Buat record inventaris
        $inventory = static::getModel()::create($data);

        // 5. Sync components to pc_components table
        $inventory->syncPcComponents($detailsData);

        return $inventory;
    }

    protected function getRedirectUrl(): string
    {
        // Ambil ID laboratorium dari record yang baru dibuat
        $labId = $this->record->laboratorium_id;

        // Redirect ke halaman index dengan filter laboratorium yang sesuai
        return $this->getResource()::getUrl('index', [
            'tableFilters' => [
                'laboratorium' => [
                    'value' => $labId
                ]
            ]
        ]);
    }
}
