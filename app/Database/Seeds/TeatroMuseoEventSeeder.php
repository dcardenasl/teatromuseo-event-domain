<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Seeder;

/**
 * Seeds representative published events for local development.
 */
final class TeatroMuseoEventSeeder extends Seeder
{
    public function run(): void
    {
        $slugStore = new \App\Libraries\Localization\PublicSlugStore(
            new \App\Models\EventPublicSlugModel(),
            new \App\Libraries\Localization\SlugGenerator(),
            new \App\Libraries\Localization\RequestLocaleResolver(),
        );

        foreach ($this->eventDefinitions() as $definition) {
            $eventId = $this->upsertRecord('events', [
                'uuid' => $definition['uuid'],
            ], [
                'title' => $definition['title'],
                'event_type' => $definition['event_type'],
                'description' => $definition['description'],
                'start_time' => $definition['start_time'],
                'end_time' => $definition['end_time'],
                'venue' => $definition['venue'],
                'capacity' => $definition['capacity'],
                'available_spots' => $definition['available_spots'],
                'status' => 'published',
            ]);

            if ($eventId === null) {
                continue;
            }

            $titlesByLocale = [];
            foreach ($this->translationRows($definition) as $translation) {
                $this->upsertRecord('event_translations', [
                    'translatable_type' => 'event',
                    'translatable_id' => $eventId,
                    'locale' => $translation['locale'],
                    'field' => 'title',
                ], [
                    'value' => $translation['title'],
                ]);

                $this->upsertRecord('event_translations', [
                    'translatable_type' => 'event',
                    'translatable_id' => $eventId,
                    'locale' => $translation['locale'],
                    'field' => 'description',
                ], [
                    'value' => $translation['description'],
                ]);

                $titlesByLocale[$translation['locale']] = $translation['title'];
            }

            $slugStore->syncForResource('event', $eventId, $titlesByLocale);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventDefinitions(): array
    {
        return [
            $this->event(1, 'evt-001', 'Función Viva', 'function', '2026-08-01 19:00:00', '2026-08-01 21:00:00', 'Sala Principal', 180, 42, 'Una función contemporánea con elenco estable y música en vivo.'),
            $this->event(2, 'evt-002', 'Festival de Luz', 'festival', '2026-08-03 19:00:00', '2026-08-03 21:00:00', 'Patio Central', 220, 35, 'Festival con programación abierta, mediación y encuentros con el público.'),
            $this->event(3, 'evt-003', 'Taller de Escena', 'workshop', '2026-08-05 18:00:00', '2026-08-05 20:00:00', 'Sala Talleres', 24, 6, 'Taller práctico para explorar cuerpo, espacio y composición escénica.'),
            $this->event(4, 'evt-004', 'Curso de Actuación', 'course', '2026-08-07 18:30:00', '2026-08-07 20:30:00', 'Sala de Formación', 30, 9, 'Curso intensivo con ejercicios de voz, escucha y construcción de personaje.'),
            $this->event(5, 'evt-005', 'Función de Cámara', 'function', '2026-08-09 19:30:00', '2026-08-09 21:00:00', 'Sala Secundaria', 120, 18, 'Propuesta íntima con cercanía al público y puesta en escena reducida.'),
            $this->event(6, 'evt-006', 'Festival de Memoria', 'festival', '2026-08-11 19:00:00', '2026-08-11 21:30:00', 'Teatro al Aire Libre', 260, 58, 'Festival dedicado a archivos, relatos y mediaciones sobre la historia del teatro.'),
            $this->event(7, 'evt-007', 'Taller de Vestuario', 'workshop', '2026-08-13 18:00:00', '2026-08-13 20:00:00', 'Camerino Histórico', 18, 4, 'Taller de confección, reparación y lectura material del vestuario escénico.'),
            $this->event(8, 'evt-008', 'Función Nocturna', 'function', '2026-08-15 20:00:00', '2026-08-15 22:00:00', 'Sala Principal', 180, 27, 'Función especial pensada para horario nocturno y experiencia inmersiva.'),
            $this->event(9, 'evt-009', 'Curso de Dramaturgia', 'course', '2026-08-17 18:30:00', '2026-08-17 20:30:00', 'Sala de Formación', 28, 11, 'Curso orientado a escritura, estructura y lectura de escenas.'),
            $this->event(10, 'evt-010', 'Festival de Verano', 'festival', '2026-08-19 19:00:00', '2026-08-19 21:30:00', 'Patio Central', 240, 49, 'Encuentro estacional con obras breves, música y mediación cultural.'),
            $this->event(11, 'evt-011', 'Taller de Iluminación', 'workshop', '2026-08-21 18:00:00', '2026-08-21 20:00:00', 'Cabina Técnica', 16, 3, 'Taller técnico sobre color, atmósfera y lectura de la luz en escena.'),
            $this->event(12, 'evt-012', 'Función de Ensayo', 'function', '2026-08-23 19:00:00', '2026-08-23 20:30:00', 'Sala de Ensayo', 100, 22, 'Función abierta que comparte el proceso de trabajo antes del estreno.'),
            $this->event(13, 'evt-013', 'Actividad Especial', 'other', '2026-08-25 19:30:00', '2026-08-25 21:00:00', 'Vestíbulo Principal', 140, 70, 'Actividad singular para conectar creación, conversación y público general.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function event(
        int $id,
        string $uuid,
        string $title,
        string $eventType,
        string $startTime,
        string $endTime,
        string $venue,
        int $capacity,
        int $availableSpots,
        string $description
    ): array {
        return [
            'id' => $id,
            'uuid' => $uuid,
            'title' => $title,
            'event_type' => $eventType,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'venue' => $venue,
            'capacity' => $capacity,
            'available_spots' => $availableSpots,
            'description' => $description,
        ];
    }

    /**
     * @param array<string, mixed> $definition
     * @return list<array{locale: string, title: string, description: string}>
     */
    private function translationRows(array $definition): array
    {
        $title = (string) ($definition['title'] ?? '');
        $eventType = (string) ($definition['event_type'] ?? 'other');
        $venue = (string) ($definition['venue'] ?? '');
        $description = (string) ($definition['description'] ?? '');

        return [
            [
                'locale' => 'es',
                'title' => $title,
                'description' => $description,
            ],
            [
                'locale' => 'en',
                'title' => $title,
                'description' => $title . ' presents a ' . $this->eventTypeLabel('en', $eventType) . ' at ' . $venue . '. ' . $this->localizedDetailPhrase('en', $eventType),
            ],
            [
                'locale' => 'fr',
                'title' => $title,
                'description' => $title . ' propose une ' . $this->eventTypeLabel('fr', $eventType) . ' à ' . $venue . '. ' . $this->localizedDetailPhrase('fr', $eventType),
            ],
            [
                'locale' => 'pt',
                'title' => $title,
                'description' => $title . ' apresenta uma ' . $this->eventTypeLabel('pt', $eventType) . ' no ' . $venue . '. ' . $this->localizedDetailPhrase('pt', $eventType),
            ],
        ];
    }

    private function eventTypeLabel(string $locale, string $eventType): string
    {
        return match ($locale . ':' . $eventType) {
            'en:function' => 'stage performance',
            'en:festival' => 'festival program',
            'en:course' => 'training course',
            'en:workshop' => 'hands-on workshop',
            'en:other' => 'special activity',
            'fr:function' => 'représentation',
            'fr:festival' => 'festival',
            'fr:course' => 'cours',
            'fr:workshop' => 'atelier',
            'fr:other' => 'activité spéciale',
            'pt:function' => 'apresentação',
            'pt:festival' => 'programação de festival',
            'pt:course' => 'curso',
            'pt:workshop' => 'oficina',
            'pt:other' => 'atividade especial',
            default => 'event',
        };
    }

    private function localizedDetailPhrase(string $locale, string $eventType): string
    {
        return match ($locale . ':' . $eventType) {
            'en:function' => 'Limited capacity and live accompaniment.',
            'en:festival' => 'Limited capacity and cultural mediation throughout the event.',
            'en:course' => 'Limited capacity with guided exercises and documentation.',
            'en:workshop' => 'Limited capacity with hands-on practice and materials.',
            'en:other' => 'Limited capacity and an open conversation with the audience.',
            'fr:function' => 'Capacité limitée et accompagnement musical.',
            'fr:festival' => 'Capacité limitée et médiation culturelle pendant toute la journée.',
            'fr:course' => 'Capacité limitée avec exercices guidés et documentation.',
            'fr:workshop' => 'Capacité limitée avec pratique accompagnée et matériel.',
            'fr:other' => 'Capacité limitée et conversation ouverte avec le public.',
            'pt:function' => 'Lotação limitada e acompanhamento ao vivo.',
            'pt:festival' => 'Lotação limitada e mediação cultural ao longo do encontro.',
            'pt:course' => 'Lotação limitada com exercícios guiados e documentação.',
            'pt:workshop' => 'Lotação limitada com prática orientada e materiais.',
            'pt:other' => 'Lotação limitada e conversa aberta com o público.',
            default => '',
        };
    }

    /**
     * @param array<string, scalar|null> $lookup
     * @param array<string, mixed> $data
     */
    private function upsertRecord(string $table, array $lookup, array $data): ?int
    {
        $supportsCreatedAt = $this->db->fieldExists('created_at', $table);
        $supportsUpdatedAt = $this->db->fieldExists('updated_at', $table);
        $supportsId = $this->db->fieldExists('id', $table);

        $existing = $this->db->table($table)
            ->where($lookup)
            ->get()
            ->getRowArray();

        $payload = array_merge($lookup, $data);
        if ($supportsUpdatedAt) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($existing === null) {
            if ($supportsCreatedAt) {
                $payload['created_at'] = date('Y-m-d H:i:s');
            }

            try {
                $this->db->table($table)->insert($payload);
                $insertId = $this->db->insertID();

                return is_numeric($insertId) ? (int) $insertId : null;
            } catch (DatabaseException) {
                $fallback = $this->db->table($table)
                    ->where($lookup)
                    ->get()
                    ->getRowArray();

                if ($fallback !== null && $supportsId && isset($fallback['id'])) {
                    $this->db->table($table)
                        ->where('id', (int) $fallback['id'])
                        ->update($payload);

                    return (int) $fallback['id'];
                }
            }

            return null;
        }

        $updatePayload = $payload;
        unset($updatePayload['created_at']);

        if ($supportsId && isset($existing['id'])) {
            $this->db->table($table)
                ->where('id', (int) $existing['id'])
                ->update($updatePayload);

            return (int) $existing['id'];
        }

        $this->db->table($table)
            ->where($lookup)
            ->update($updatePayload);

        return null;
    }
}
