<?php

namespace App\Filament\Widgets;
use App\Models\Location;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Cheesegrits\FilamentGoogleMaps\Widgets\MapWidget;

class SpotsMap extends MapWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = '';

    protected static ?int $sort = 1;

    protected static ?string $icon = null;
    protected static ?string $pollingInterval = null;

    protected static ?bool $clustering = true;

    protected static ?bool $fitToBounds = true;

    protected static ?int $zoom = 12;

    protected static ?string $markerAction = 'markerAction';




    protected function getData(): array
    {
    	/**
    	 * You can use whatever query you want here, as long as it produces a set of records with your
    	 * lat and lng fields in them.
    	 */
        $locations = Location::all()->toArray();

        $data = [];

        foreach ($locations as $location)
        {
			/**
			 * Each element in the returned data must be an array
			 * containing a 'location' array of 'lat' and 'lng',
			 * and a 'label' string (optional but recommended by Google
			 * for accessibility.
			 *
			 * You should also include an 'id' attribute for internal use by this plugin
			 */
            $data[] = [
                'location'  => [
                    'lat' => $location['lat'] ? round(floatval($location['lat']), static::$precision) : 0,
                    'lng' => $location['lng'] ? round(floatval($location['lng']), static::$precision) : 0,
                ],

                'id' => $location['id'],
				'icon' => [
					'url' => url('images/red-marker.svg'),
					'type' => 'svg',
					'scale' => [35,35],
				],
            ];
        }

        return $data;
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();

        // Disable points of interest
        $config['mapConfig']['styles'] = [
            [
                'featureType' => 'poi',
                'elementType' => 'labels',
                'stylers'     => [
                    ['visibility' => 'off'],
                ],
            ],
        ];

        return $config;
    }

    public function markerAction(): Action
    {
        return Action::make('markerAction')
            ->label('Parking spot details')
            ->infolist([
                Grid::make()
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('address'),
                    ])
                    ->columns(1)
            ])
            ->record(function (array $arguments) {
                return array_key_exists('model_id', $arguments) ? Location::find($arguments['model_id']) : null;
            })
            ->modalSubmitAction(false);
    }
}
