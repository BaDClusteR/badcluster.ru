import { useState } from 'react';
import { useNavigate } from 'react-router';
import {
  Button,
  PasswordInput,
  Stack,
  TextInput,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { IconLock } from '@tabler/icons-react';
import { notify } from '@/lib/notify';
import classes from './Login.module.css';

export function LoginPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);

  const form = useForm({
    initialValues: { login: '', password: '' },
    validate: {
      login: (v) => (v.trim().length === 0 ? 'Enter login' : null),
      password: (v) => (v.length === 0 ? 'Enter password' : null),
    },
  });

  async function handleSubmit(values: typeof form.values) {
    setLoading(true);
    try {
      const res = await fetch('/api/admin/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(values),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => null);
        throw new Error(data?.error ?? 'Login failed');
      }
      notify.success('Welcome back');
      navigate('/admin');
    } catch (err) {
      notify.error('Error', err instanceof Error ? err.message : 'Login failed');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className={classes.screen}>
      <div className={classes.card}>
        <Stack gap="lg">
          <div className={classes.logo}>
            <IconLock size={28} />
          </div>

          <div>
            <h2 className={classes.title}>Admin Panel</h2>
            <p className={classes.subtitle}>Sign in to continue</p>
          </div>

          <form onSubmit={form.onSubmit(handleSubmit)}>
            <Stack gap="md">
              <TextInput
                label="Login"
                placeholder="admin"
                autoFocus
                {...form.getInputProps('login')}
              />
              <PasswordInput
                label="Password"
                placeholder="********"
                {...form.getInputProps('password')}
              />
              <Button type="submit" fullWidth loading={loading} mt="xs">
                Sign in
              </Button>
            </Stack>
          </form>
        </Stack>
      </div>
    </div>
  );
}