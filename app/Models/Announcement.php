<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    public const TYPE_PROMO = 'promo';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_AD = 'ad';

    protected $fillable = [
        'type',
        'title',
        'message',
        'link_url',
        'link_label',
        'badge_label',
        'image_path',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Item yang layak tampil di papan pengumuman: aktif dan masih dalam
     * periode tayang (jika dijadwalkan).
     */
    public function scopeLive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    /**
     * Warna aksen kartu berdasarkan tipe item.
     */
    public function accentClasses(): string
    {
        return match ($this->type) {
            self::TYPE_PROMO => 'from-rose-600 to-rose-700 text-rose-100',
            self::TYPE_ANNOUNCEMENT => 'from-sky-600 to-sky-700 text-sky-100',
            self::TYPE_AD => 'from-amber-500 to-amber-600 text-amber-100',
            default => 'from-slate-700 to-slate-800 text-slate-100',
        };
    }

    public function badgeClasses(): string
    {
        return 'bg-white/20 text-white';
    }
}
