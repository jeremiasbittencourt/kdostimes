import {z} from 'zod';

export const loginSchema = z.object({
  email: z.string().email('E-mail inválido.'),
  password: z.string().min(6, 'Mínimo 6 caracteres.'),
});

export type LoginInput = z.infer<typeof loginSchema>;

export const registerSchema = z
  .object({
    name: z.string().min(2, 'Nome muito curto.').max(120),
    email: z.string().email('E-mail inválido.'),
    password: z.string().min(8, 'Mínimo 8 caracteres.'),
    password_confirmation: z.string(),
  })
  .refine(data => data.password === data.password_confirmation, {
    message: 'Senhas não conferem.',
    path: ['password_confirmation'],
  });

export type RegisterInput = z.infer<typeof registerSchema>;

export const forgotPasswordSchema = z.object({
  email: z.string().email('E-mail inválido.'),
});

export type ForgotPasswordInput = z.infer<typeof forgotPasswordSchema>;
