<?php

namespace App\Services;

class CategorySuggestionService
{
    /**
     * Get predefined categories for fixed expenses
     */
    public static function getFixedExpenseCategories(): array
    {
        return [
            'Alquiler/Vivienda' => ['Renta', 'Hipoteca', 'Alquiler', 'Casa', 'Vivienda'],
            'Servicios Básicos' => ['Luz', 'Agua', 'Gas', 'Electricidad', 'Internet', 'Teléfono', 'Servicio'],
            'Transporte' => ['Transporte público', 'Gasolina', 'Estacionamiento', 'Peaje', 'Taxi', 'Uber'],
            'Seguros' => ['Seguro de auto', 'Seguro de vida', 'Seguro médico', 'Seguro hogar'],
            'Educación' => ['Colegiatura', 'Libros', 'Material escolar', 'Cursos', 'Universidad'],
            'Suscripciones' => ['Netflix', 'Spotify', 'Amazon Prime', 'Disney+', 'HBO', 'Revistas'],
            'Créditos' => ['Pago de crédito', 'Pago de préstamo', 'Pago de tarjeta'],
            'Impuestos' => ['Impuestos', 'Predial', 'ISR', 'IVA'],
            'Mantenimiento' => ['Reparaciones', 'Mantenimiento', 'Limpieza'],
        ];
    }

    /**
     * Get predefined categories for variable expenses
     */
    public static function getVariableExpenseCategories(): array
    {
        return [
            'Alimentación' => ['Comida', 'Restaurantes', 'Supermercado', 'Despensa', 'Comestibles'],
            'Entretenimiento' => ['Cine', 'Conciertos', 'Teatro', 'Eventos', 'Fiestas'],
            'Ropa y Accesorios' => ['Ropa', 'Zapatos', 'Accesorios', 'Joyería', 'Belleza'],
            'Salud' => ['Farmacia', 'Consultas médicas', 'Medicamentos', 'Dentista', 'Óptica'],
            'Viajes' => ['Hotel', 'Vuelos', 'Turismo', 'Vacaciones', 'Transporte aéreo'],
            'Hogar' => ['Muebles', 'Decoración', 'Electrodomésticos', 'Herramientas'],
            'Tecnología' => ['Computadora', 'Teléfono', 'Tablet', 'Accesorios tech', 'Software'],
            'Regalos' => ['Regalos', 'Cumpleaños', 'Navidad', 'Aniversarios'],
            'Mascotas' => ['Veterinario', 'Comida mascotas', 'Accesorios mascotas'],
            'Otros' => ['Varios', 'Misceláneo', 'Otros gastos'],
        ];
    }

    /**
     * Get all predefined categories
     */
    public static function getAllCategories(): array
    {
        return array_merge(
            self::getFixedExpenseCategories(),
            self::getVariableExpenseCategories()
        );
    }

    /**
     * Suggest expense type based on category keywords
     */
    public static function suggestExpenseType(string $category): ?string
    {
        $categoryLower = strtolower($category);

        // Check fixed expense categories
        foreach (self::getFixedExpenseCategories() as $mainCategory => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($categoryLower, strtolower($keyword))) {
                    return 'fixed';
                }
            }
        }

        // Check variable expense categories
        foreach (self::getVariableExpenseCategories() as $mainCategory => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($categoryLower, strtolower($keyword))) {
                    return 'variable';
                }
            }
        }

        return null; // No suggestion
    }

    /**
     * Get category suggestions based on partial input
     */
    public static function getCategorySuggestions(string $partial): array
    {
        $suggestions = [];
        $partialLower = strtolower($partial);

        foreach (self::getAllCategories() as $mainCategory => $keywords) {
            // Check main category
            if (str_contains(strtolower($mainCategory), $partialLower)) {
                $suggestions[] = $mainCategory;
            }

            // Check keywords
            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($keyword), $partialLower) && !in_array($keyword, $suggestions)) {
                    $suggestions[] = $keyword;
                }
            }
        }

        return array_slice($suggestions, 0, 10); // Limit to 10 suggestions
    }

    /**
     * Get popular categories for quick selection
     */
    public static function getPopularCategories(): array
    {
        return [
            'fixed' => [
                'Renta',
                'Luz',
                'Agua',
                'Internet',
                'Transporte público',
                'Seguro de auto',
                'Teléfono',
                'Suscripciones'
            ],
            'variable' => [
                'Supermercado',
                'Restaurantes',
                'Gasolina',
                'Entretenimiento',
                'Ropa',
                'Farmacia',
                'Regalos',
                'Viajes'
            ]
        ];
    }
}
