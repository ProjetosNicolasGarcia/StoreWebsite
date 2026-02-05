<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model responsável pela representação dos Banners Publicitários.
 * Mapeia a tabela 'banners' no banco de dados.
 */
class Banner extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * Definimos explicitamente quais campos podem ser preenchidos via create/update.
     * Isso protege o model contra injeção de campos maliciosos (Mass Assignment Protection).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'image_url',    // Caminho relativo da imagem no Storage
        'title',        // Título usado para SEO e Acessibilidade (Alt Text)
        'description',  // Texto de apoio ou subtítulo
        'link_url',     // URL de destino ao clicar no banner
        'position',     // Ordem de classificação (numérico)
        'is_active',    // Controle de visibilidade (booleano)
        'location',     // Define onde o banner aparece (ex: 'hero', 'footer')
    ];

    /**
     * The attributes that should be cast.
     *
     * Garante que os dados venham do banco já tipados corretamente.
     * Ex: 'is_active' virá como true/false, não como 1/0.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];
}