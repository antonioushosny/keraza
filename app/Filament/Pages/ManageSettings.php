<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'الإعدادات العامة';

    protected static ?string $navigationLabel = 'الإعدادات العامة';

    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $settings = Setting::getSettings();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('لوحة الشرف')
                    ->schema([
                        Toggle::make('honor_roll_limit_enabled')
                            ->label('تحديد عدد الطلاب في لوحة الشرف')
                            ->live(),
                        TextInput::make('honor_roll_limit')
                            ->label('عدد الطلاب في لوحة الشرف')
                            ->numeric()
                            ->required()
                            ->visible(fn ($get) => $get('honor_roll_limit_enabled')),
                        Toggle::make('show_zero_scores')
                            ->label('عرض الطلاب الحاصلين على صفر في لوحة الشرف'),
                    ])->columns(2),

                Section::make('بوابة أولياء الأمور')
                    ->schema([
                        Toggle::make('show_attendance_percentage')
                            ->label('عرض نسبة الحضور لأولياء الأمور'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ التغييرات')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $settings = Setting::getSettings();
        $settings->update($this->form->getState());

        Notification::make()
            ->title('تم حفظ الإعدادات بنجاح')
            ->success()
            ->send();
    }
}
