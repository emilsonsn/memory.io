<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Valor')
                    ->numeric()
                    ->prefix('R$')
                    ->required()
                    ->minValue(0),
                TextInput::make('max_memories')
                    ->label('Limite de memórias')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Deixe vazio para não limitar.'),
                TextInput::make('max_categories')
                    ->label('Limite de categorias')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Deixe vazio para não limitar.'),
                Toggle::make('can_export')
                    ->label('Permite exportação'),
                Toggle::make('can_use_ai')
                    ->label('Permite uso de IA'),
            ]);
    }
}
