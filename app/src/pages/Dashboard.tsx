import { Button, Card, Grid, Group, Text, Title, ThemeIcon } from '@mantine/core';
import { IconUsers, IconFiles, IconEye, IconActivity } from '@tabler/icons-react';
import { notify } from '@/lib/notify';

const stats = [
  { label: 'Users', value: '—', icon: IconUsers, color: 'indigo' },
  { label: 'Pages', value: '—', icon: IconFiles, color: 'teal' },
  { label: 'Views today', value: '—', icon: IconEye, color: 'orange' },
  { label: 'Uptime', value: '—', icon: IconActivity, color: 'green' },
];

export function DashboardPage() {
  return (
    <>
      <Title order={2} mb="lg">
        Dashboard
      </Title>

      <Group mb="lg">
        <Button variant="light" color="teal" onClick={() => notify.success('Saved', 'Everything is fine')}>
          Success toast
        </Button>
        <Button variant="light" color="red" onClick={() => notify.error('Oops', 'Something broke')}>
          Error toast
        </Button>
        <Button variant="light" color="blue" onClick={() => notify.info('FYI', 'Just so you know')}>
          Info toast
        </Button>
        <Button variant="light" color="yellow" onClick={() => notify.warning('Careful', 'This is a warning')}>
          Warning toast
        </Button>
      </Group>

      <Grid>
        {stats.map((s) => (
          <Grid.Col key={s.label} span={{ base: 12, sm: 6, lg: 3 }}>
            <Card
              padding="lg"
              radius="md"
              style={{
                background: 'var(--mantine-color-dark-7)',
                border: '1px solid var(--mantine-color-dark-5)',
              }}
            >
              <Group justify="space-between">
                <div>
                  <Text c="dimmed" size="xs" tt="uppercase" fw={700}>
                    {s.label}
                  </Text>
                  <Text size="xl" fw={700} mt={4}>
                    {s.value}
                  </Text>
                </div>
                <ThemeIcon size={48} radius="md" variant="light" color={s.color}>
                  <s.icon size={24} />
                </ThemeIcon>
              </Group>
            </Card>
          </Grid.Col>
        ))}
      </Grid>
    </>
  );
}