<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RequestingUnitDvOverview;
use App\Models\DisbursementVoucher;
use App\Models\Fund;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
class RequestingUnitDashboard extends Page
{
    protected string $view = 'filament.pages.requesting-unit-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Requesting Unit';

    protected static ?string $title = 'Requesting Unit Dashboard';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('Requesting Unit') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    TextInput::make('dv_no')
                        ->label('DV No.')
                        ->required()
                        ->unique(DisbursementVoucher::class, 'dv_no'),
                    TextInput::make('payee_name')
                        ->required(),
                    Select::make('fund_id')
                        ->label('Fund')
                        ->options(fn () => Fund::query()->where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('amount')
                        ->numeric()
                        ->prefix('₱')
                        ->required(),
                    Textarea::make('particulars')
                        ->required()
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('createDisbursementVoucher')
                    ->footer([
                        Actions::make([
                            Action::make('createDisbursementVoucher')
                                ->label('Submit disbursement voucher')
                                ->submit('createDisbursementVoucher'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function createDisbursementVoucher(): void
    {
        $data = $this->form->getState();

        DisbursementVoucher::create([
            ...$data,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        Notification::make()
            ->success()
            ->title('Disbursement voucher submitted')
            ->send();

        $this->form->fill();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RequestingUnitDvOverview::class,
        ];
    }
}
