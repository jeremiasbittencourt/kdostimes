import {z} from 'zod';

export const createPeladaSchema = z.object({
  name: z.string().min(3, 'Mínimo 3 caracteres.').max(120),
  location: z.string().max(180).optional().nullable(),
  day_of_week: z
    .number({invalid_type_error: 'Escolha o dia.'})
    .int()
    .min(0)
    .max(6),
  time: z
    .string()
    .regex(/^\d{2}:\d{2}$/, 'Use formato HH:mm.'),
  players_per_team: z
    .number({invalid_type_error: 'Informe os jogadores por time.'})
    .int()
    .min(3)
    .max(11),
  has_fixed_goalkeepers: z.boolean(),
  confirmation_deadline_hours: z
    .number({invalid_type_error: 'Informe o prazo.'})
    .int()
    .min(1)
    .max(24),
});

export type CreatePeladaInput = z.infer<typeof createPeladaSchema>;

export const joinPeladaSchema = z
  .object({
    short_code: z.string().max(16).optional(),
    invite_token: z.string().max(80).optional(),
  })
  .refine(data => Boolean(data.short_code || data.invite_token), {
    message: 'Informe o código da pelada ou use um link de convite.',
    path: ['short_code'],
  });

export type JoinPeladaInput = z.infer<typeof joinPeladaSchema>;
