<?php namespace App\Models;

use CodeIgniter\Model;

class CachedBookingModel extends Model
{
    protected $table = 'cached_bookings';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'external_id',
        'region',
        'data',
        'pricing',
        'last_synced',
        'pricing_updated',
    ];

    public function getByExternalIdAndRegion(string $externalId, string $region)
    {
        return $this->where('external_id', $externalId)
                    ->where('region', $region)
                    ->first();
    }
}
