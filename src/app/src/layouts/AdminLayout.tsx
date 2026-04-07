import { Suspense } from 'react';
import { Outlet, NavLink as RouterNavLink, useLocation, useNavigate } from 'react-router';
import { notify } from '@/lib/notify';
import {
  AppShell,
  Box,
  Burger,
  Group,
  Loader,
  NavLink,
  ScrollArea,
  Text,
  Title,
  useMantineColorScheme,
  ActionIcon,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import {
  IconDashboard,
  IconSun,
  IconMoon,
  IconLogout,
  IconChartBar,
  IconSettings,
  IconDatabase,
  IconPhoto,
  IconFileText,
  IconFile,
  IconUsers,
  IconShield,
  IconKey,
  IconWorld,
  IconBrush,
  IconPlug,
  type IconProps,
} from '@tabler/icons-react';
import type { ComponentType } from 'react';
import type { ResolvedModule } from '@/modules/types';

type TablerIcon = ComponentType<IconProps>;

/** Map of icon names to Tabler icon components */
const iconMap: Record<string, TablerIcon> = {
  'chart-bar': IconChartBar,
  settings: IconSettings,
  database: IconDatabase,
  photo: IconPhoto,
  'file-text': IconFileText,
};

interface NavItem {
  label: string;
  icon: TablerIcon;
  path?: string;
  children?: NavItem[];
}

interface AdminLayoutProps {
  modules: ResolvedModule[];
  loading: boolean;
}

/** Recursively check whether any descendant path matches the current location */
function isItemActive(item: NavItem, pathname: string): boolean {
  if (item.path) {
    if (item.path === '/admin') return pathname === '/admin';
    if (pathname === item.path || pathname.startsWith(item.path + '/')) return true;
  }
  return item.children?.some((c) => isItemActive(c, pathname)) ?? false;
}

const navStyles = {
  root: { borderRadius: 'var(--mantine-radius-md)', marginBottom: 4 },
};

function NavItemView({ item, pathname }: { item: NavItem; pathname: string }) {
  const active = isItemActive(item, pathname);

  if (item.children?.length) {
    return (
      <NavLink
        label={item.label}
        leftSection={<item.icon size={18} />}
        active={active}
        defaultOpened={active}
        styles={navStyles}
      >
        {item.children.map((child) => (
          <NavItemView key={child.label} item={child} pathname={pathname} />
        ))}
      </NavLink>
    );
  }

  return (
    <NavLink
      component={RouterNavLink}
      to={item.path!}
      end={item.path === '/admin'}
      label={item.label}
      leftSection={<item.icon size={18} />}
      active={active}
      styles={navStyles}
    />
  );
}

export function AdminLayout({ modules, loading }: AdminLayoutProps) {
  const [opened, { toggle }] = useDisclosure();
  const location = useLocation();
  const navigate = useNavigate();
  const { colorScheme, toggleColorScheme } = useMantineColorScheme();

  async function handleLogout() {
    try {
      await fetch('/api/admin/logout', { method: 'POST' });
    } catch {
      // Network error — still log out on the client
    }
    notify.success('Signed out');
    navigate('/admin/login', { replace: true });
  }

  const navItems: NavItem[] = [
    { label: 'Dashboard', path: '/admin', icon: IconDashboard },
    { label: 'Pages', path: '/admin/pages', icon: IconFile },
    ...modules.map<NavItem>((m) => ({
      label: m.label,
      path: `/admin/${m.path}`,
      icon: iconMap[m.icon ?? ''] ?? IconDashboard,
    })),
    {
      label: 'Users',
      icon: IconUsers,
      children: [
        { label: 'All users', path: '/admin/users', icon: IconUsers },
        { label: 'Roles', path: '/admin/users/roles', icon: IconShield },
        { label: 'Permissions', path: '/admin/users/permissions', icon: IconKey },
      ],
    },
    {
      label: 'Settings',
      icon: IconSettings,
      children: [
        { label: 'General', path: '/admin/settings/general', icon: IconWorld },
        { label: 'Appearance', path: '/admin/settings/appearance', icon: IconBrush },
        { label: 'Integrations', path: '/admin/settings/integrations', icon: IconPlug },
      ],
    },
  ];

  return (
    <AppShell
      header={{ height: 56 }}
      navbar={{
        width: 260,
        breakpoint: 'sm',
        collapsed: { mobile: !opened },
      }}
      padding="md"
      styles={{
        main: {
          background: 'var(--mantine-color-dark-8)',
          minHeight: '100vh',
        },
        header: {
          background: 'var(--mantine-color-dark-7)',
          borderBottom: '1px solid var(--mantine-color-dark-5)',
        },
        navbar: {
          background: 'var(--mantine-color-dark-7)',
          borderRight: '1px solid var(--mantine-color-dark-5)',
        },
      }}
    >
      {/* Header */}
      <AppShell.Header>
        <Group h="100%" px="md" justify="space-between">
          <Group>
            <Burger opened={opened} onClick={toggle} hiddenFrom="sm" size="sm" />
            <Title order={4} style={{ letterSpacing: '-0.02em' }}>
              BC Admin
            </Title>
          </Group>
          <Group gap="xs">
            <ActionIcon
              variant="subtle"
              size="lg"
              onClick={toggleColorScheme}
              aria-label="Toggle theme"
            >
              {colorScheme === 'dark' ? <IconSun size={18} /> : <IconMoon size={18} />}
            </ActionIcon>
            <ActionIcon
              variant="subtle"
              size="lg"
              color="red"
              onClick={handleLogout}
              aria-label="Logout"
            >
              <IconLogout size={18} />
            </ActionIcon>
          </Group>
        </Group>
      </AppShell.Header>

      {/* Sidebar */}
      <AppShell.Navbar>
        <ScrollArea p="xs" style={{ flex: 1 }}>
          {navItems.map((item) => (
            <NavItemView key={item.label} item={item} pathname={location.pathname} />
          ))}

          {loading && (
            <Box p="md" ta="center">
              <Loader size="sm" />
              <Text size="xs" c="dimmed" mt="xs">
                Loading modules...
              </Text>
            </Box>
          )}
        </ScrollArea>
      </AppShell.Navbar>

      {/* Content */}
      <AppShell.Main>
        <Suspense
          fallback={
            <Box
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                minHeight: 300,
              }}
            >
              <Loader />
            </Box>
          }
        >
          <Outlet />
        </Suspense>
      </AppShell.Main>
    </AppShell>
  );
}