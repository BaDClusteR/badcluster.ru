import { Suspense } from 'react';
import { Outlet, NavLink as RouterNavLink, useLocation} from 'react-router';
import {
  AppShell,
  Box,
  Loader,
  NavLink,
  Overlay,
  ScrollArea,
  Text,
  Title,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import {
  IconDashboard,
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
import classes from "./AdminLayout.module.css"
import clsx from "clsx";
import logoUrl from '@/static/img/logo.svg';
import Header from "@/layout/Header.tsx";

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

function NavItemView({ item, pathname, level = 1}: { item: NavItem; pathname: string, level?: number }) {
  const active = isItemActive(item, pathname);

  if (item.children?.length) {
    return (
      <NavLink
        className={clsx(classes.navbarLink, classes[`navbarLinkLevel${level}`])}
        classNames={{
          collapse: classes.navbarLinkCollapse,
          children: classes.navbarLinkChildren
        }}
        label={item.label}
        leftSection={
          <span className={classes.navbarLinkIcon}>
            <item.icon size={16} />
          </span>
        }
        active={active}
        defaultOpened={active}
        styles={navStyles}
      >
        {item.children.map((child) => (
          <NavItemView level={level + 1} key={child.label} item={child} pathname={pathname} />
        ))}
      </NavLink>
    );
  }

  return (
    <NavLink
      component={RouterNavLink}
      className={clsx(classes.navbarLink, classes[`navbarLinkLevel${level}`])}
      to={item.path!}
      end={item.path === '/admin'}
      label={item.label}
      leftSection={
        <span className={classes.navbarLinkIcon}>
          <item.icon size={16} />
        </span>
      }
      active={active}
      styles={navStyles}
    />
  );
}

export function AdminLayout({ modules, loading }: AdminLayoutProps) {
  const [mobileOpened, { toggle: toggleMobile }] = useDisclosure();
  const [desktopOpened, { toggle: toggleDesktop }] = useDisclosure(true);
  const location = useLocation();

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
      layout="alt"
      header={{ height: 56 }}
      navbar={{
        width: 260,
        breakpoint: 'sm',
        collapsed: { mobile: !mobileOpened, desktop: !desktopOpened },
      }}
      padding="md"
    >
      <Header
        mobileOpened={mobileOpened}
        desktopOpened={desktopOpened}
        toggleMobile={toggleMobile}
        toggleDesktop={toggleDesktop}
      />
      {/* Sidebar */}
      <AppShell.Navbar className={classes.navbar} zIndex={200} withBorder={false}>
        <div className={classes.navbarTitleContainer}>
          <img src={logoUrl} alt="BC Logo" />
          <Title order={4} style={{ letterSpacing: '-0.02em' }}>
            BC Admin
          </Title>
        </div>
        <ScrollArea style={{ flex: 1 }} classNames={{content: classes.navbarScrollContent}}>
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
      <AppShell.Main className={classes.main}>
        <Overlay
          color="#000"
          backgroundOpacity={0.5}
          zIndex={199}
          fixed
          onClick={toggleMobile}
          className={clsx(classes.navbarOverlay, mobileOpened && classes.navbarOverlayVisible)}
        />
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
