<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'event_';
    public $timestamps = false;
    protected $primaryKey = 'event_id';

    protected $fillable = [
        'name',
        'location',
        'description',
        'start_timestamp',
        'end_timestamp',
        'creator_id',
    ];

    protected $casts = [
        'private' => 'boolean',
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
    ];


    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function ticketTypes()
    {
        return $this->hasMany(TicketType::class, 'event_id', 'event_id')->orderBy('price', 'asc');

    }

    public function comments()
    {
        if (auth()->check()){
        return $this->hasMany(Comment::class, 'event_id')
                    ->orderByRaw("author_id = ? DESC", [auth()->user()->user_id])
                    ->orderByDesc('likes');}

        else{
            return $this->hasMany(Comment::class, 'event_id')
                    
                    ->orderByDesc('likes');
        }
    }
    

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'event_id', 'event_id');
    }
    public function getAverageRatingAttribute()
    {
        $ratings = $this->ratings;
        $totalRatings = $ratings->count();

        if ($totalRatings > 0) {
            $sum = $ratings->sum('rating');
            return $sum / $totalRatings;
        }

        return 0; 
    }
    public function userRating()
    {
        if (auth()->check()) {
            $userId = auth()->user()->user_id;

            return $this->ratings()->where('author_id', $userId)->first();
        }

        return null;
    }
    public function soldTickets()
    {
        return TicketInstance::whereIn('ticket_type_id', $this->ticketTypes->pluck('ticket_type_id'))
            ->get();
    }

    public function getTotalSoldTickets()
    {
        return $this->soldTickets()->count();
    }

    public function tickets_chart()
    {
        $ticketInstances = TicketInstance::whereIn('ticket_type_id', $this->ticketTypes->pluck('ticket_type_id'))->get();
    
        $dataByDate = $ticketInstances->groupBy(function ($item) {
            return $item->order->timestamp->format('Y-m-d');
        });
    
        $labels = $dataByDate->keys()->sort()->toArray();
    
        $datasets = [];
        
        $colorPalette = [ '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf', '#FF5733', '#33FF57', '#5733FF', '#FF33ED', '#FF3371'];
    
        foreach ($this->ticketTypes as $key => $ticketType) {
            $typeData = $dataByDate->map(function ($items) use ($ticketType) {
                return $items->where('ticket_type_id', $ticketType->ticket_type_id)->count();
            })->values()->toArray();
    
            $colorIndex = $key % count($colorPalette);
    
            $datasets[] = [
                'label' => $ticketType->name,
                'data' => $typeData,
                'borderWidth' => 1,
                'backgroundColor' => $colorPalette[$colorIndex],
                'borderColor' => $colorPalette[$colorIndex],
            ];
        }
    
        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
    

    public function all_tickets_chart()
{
    $ticketInstances = TicketInstance::whereIn('ticket_type_id', $this->ticketTypes->pluck('ticket_type_id'))->get();

    $dataByDate = $ticketInstances->groupBy(function ($item) {
        return $item->order->timestamp->format('Y-m-d');
    });

    $labels = $dataByDate->keys()->sort()->toArray();

    $totalTicketsData = [];

    foreach ($labels as $date) {
        $totalTicketsData[] = $dataByDate[$date]->count();
    }

    $colorPalette = ['#1f77b4'];

    $datasets[] = [
        'label' => 'Total de Bilhetes Vendidos',
        'data' => $totalTicketsData,
        'borderWidth' => 1,
        'backgroundColor' => $colorPalette[0], 
        'borderColor' => $colorPalette[0],
    ];

    return [
        'labels' => $labels,
        'datasets' => $datasets,
    ];
}

    

    public function tickets_pie_chart()
    {
        $ticketInstances = TicketInstance::whereIn('ticket_type_id', $this->ticketTypes->pluck('ticket_type_id'))->get();
    
        $totalTickets = $ticketInstances->count();
    
        $colorPalette = ['#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf', '#FF5733', '#33FF57', '#5733FF', '#FF33ED', '#FF3371'];
    
        while (count($this->ticketTypes) > count($colorPalette)) {
            $colorPalette[] = '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        }
    
        $data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'My First Dataset',
                    'data' => [],
                    'backgroundColor' => [],
                    'hoverOffset' => 4,
                ],
            ],
        ];
    
        foreach ($this->ticketTypes as $key => $ticketType) {
            $typeCount = $ticketInstances->where('ticket_type_id', $ticketType->ticket_type_id)->count();
    
            $percentage = ($totalTickets > 0) ? ($typeCount / $totalTickets) * 100 : 0;
    
            $data['labels'][] = $ticketType->name;
            $data['datasets'][0]['data'][] = $percentage;
    
            $data['datasets'][0]['backgroundColor'][] = array_shift($colorPalette);
        }
    
        return $data;
    }
    
    public function per_sold_tickets_pie_chart($ticketTypeId)
    {
        $ticketType = TicketType::findOrFail($ticketTypeId);    
        $typeTicketInstances = TicketInstance::where('ticket_type_id', $ticketType->ticket_type_id)->get();
    
        $ticketsSold = $typeTicketInstances->count();
        $stock = $ticketType->stock;
    
        $percentageFilled = ($ticketsSold * 100) / ($stock + $ticketsSold);
    

        $colorPalette = [ '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf', '#FF5733', '#33FF57', '#5733FF', '#FF33ED', '#FF3371'];



        $pieChartData = [
            'label' => $ticketType->name,
            'data' => [
                'labels' => [$ticketType->name],
                'datasets' => [
                    [
                        'data' => [$percentageFilled, 100 - $percentageFilled],
                        'backgroundColor' => [
                            '#1f77b4', 
                            '#ffffff',
                        ],
                        'borderColor' => '#1f77b4', 
                        'borderWidth' => 1,
                        'hoverOffset' => 4,
                    ],
                ],
            ],
        ];
    

        return $pieChartData;
    }
    
    public function calculateRevenue()
    {
        $totalRevenue = 0;

        foreach ($this->ticketTypes as $ticketType) {
            $typeInstances = TicketInstance::where('ticket_type_id', $ticketType->ticket_type_id)->get();

            $typeRevenue = $typeInstances->count() * $ticketType->price;
            $totalRevenue += $typeRevenue;
        }

        return $totalRevenue;
    }

    public static function countEvents()
    {
        try {
            $count = self::query()->count();
            return $count;
        } catch (\Exception $e) {
            echo '<script>console.error("countEvents - erro ao contar eventos: ' . $e->getMessage() . '");</script>';
            return 0;
        }
    }

    public static function countActiveEvents()
    {
        $count = self::where('private', true)->count();
        return $count;
    }

    public static function countInactiveEvents()
    {
        $count = self::where('private', false)->count();
        return $count;
    }

    public static function countEventsByMonth($month)
    {
        try {
            $count = self::whereMonth('start_timestamp', $month)->count();
            return $count;
        } catch (\Exception $e) {
            echo '<script>console.error("countEventsByMonth - erro ao contar eventos: ' . $e->getMessage() . '");</script>';
            return 0;
        }
    }

    public static function countEventsByDay($day)
    {
        try {
            $count = self::whereDay('start_timestamp', $day)->count();
            return $count;
        } catch (\Exception $e) {
            echo '<script>console.error("countEventsByDay - erro ao contar eventos: ' . $e->getMessage() . '");</script>';
            return 0; 
        }
    }

    public static function countEventsByYear($year)
    {
        try {
            $count = self::whereYear('start_timestamp', $year)->count();
            return $count;
        } catch (\Exception $e) {
            echo '<script>console.error("countEventsByYear - erro ao contar eventos: ' . $e->getMessage() . '");</script>';
            return 0; 
        }
    }


    public function images()
    {
        return $this->hasMany(EventImage::class, 'event_id');
    }
}
