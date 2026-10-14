import {z} from 'zod';

const positionEnum = z.enum(['GOL', 'ZAG', 'LAT', 'MEI', 'ATA']);

export const updateProfileSchema = z.object({
  name: z.string().min(2).max(120).optional(),
  skill_rating: z.number().int().min(1).max(10).optional(),
  positions: z
    .array(
      z.object({
        position: positionEnum,
        priority: z.union([z.literal(1), z.literal(2)]),
      }),
    )
    .max(2)
    .optional()
    .refine(list => !list || new Set(list.map(p => p.priority)).size === list.length, {
      message: 'Não repita prioridades.',
    })
    .refine(list => !list || new Set(list.map(p => p.position)).size === list.length, {
      message: 'Não repita posições.',
    }),
});

export type UpdateProfileInput = z.infer<typeof updateProfileSchema>;
