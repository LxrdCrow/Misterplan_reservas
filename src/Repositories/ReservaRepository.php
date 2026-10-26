<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReservaRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Obtiene todas las reservas ordenadas por fecha de entrada.
     *
     * @return list<array<string, mixed>>
     */
    public function findAll(): array
    {
        $sql = '
            SELECT
                id,
                nombre_huesped,
                email_huesped,
                nombre_alojamiento,
                fecha_entrada,
                fecha_salida,
                estado,
                importe,
                notas,
                fecha_creacion
            FROM reserva
            ORDER BY fecha_entrada ASC, id ASC
        ';

        $statement = $this->pdo->query($sql);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}