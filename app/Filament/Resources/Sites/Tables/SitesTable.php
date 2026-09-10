<?php

namespace App\Filament\Resources\Sites\Tables;

use App\Models\Site;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('url')
                    ->label('Link')
                    ->state(fn (Site $record) => $record->url())
                    ->copyable()
                    ->copyMessage('Link kopiert')
                    ->icon('heroicon-o-clipboard'),
                IconColumn::make('password')
                    ->label('Passwort')
                    ->boolean()
                    ->state(fn (Site $record) => filled($record->password)),
                TextColumn::make('created_at')->label('Erstellt')->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Öffnen')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Site $record) => $record->url())
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
