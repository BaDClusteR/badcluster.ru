import { useNavigate } from 'react-router';
import { Stack, Text, Title } from '@mantine/core';
import Button from '@/components/primitives/Button';

export function NotFoundPage() {
  const navigate = useNavigate();

  return (
    <Stack align="center" gap="md" py="xl">
      <Title order={1} c="dimmed" style={{ fontSize: 72, lineHeight: 1 }}>404</Title>
      <Text c="dimmed" size="lg">Страница не найдена</Text>
      <Button variant="subtle" onClick={() => navigate('/admin')}>
        На главную
      </Button>
    </Stack>
  );
}