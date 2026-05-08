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
import type { NavItemDescriptor, ResolvedModule } from '@/modules/types';
import { AdminCoreProvider } from '@/modules/AdminCoreContext';
import classes from "./AdminLayout.module.css"
import clsx from "clsx";
import logoUrl from '@/static/img/logo.svg';
import Header from "@/layout/Header.tsx";

type TablerIcon = ComponentType<IconProps>;

/** Map of icon names to Tabler icon components */
const iconMap: Record<string, TablerIcon> = {
  dashboard: IconDashboard,
  'chart-bar': IconChartBar,
  settings: IconSettings,
  database: IconDatabase,
  photo: IconPhoto,
  'file-text': IconFileText,
  file: IconFile,
  users: IconUsers,
  shield: IconShield,
  key: IconKey,
  world: IconWorld,
  brush: IconBrush,
  plug: IconPlug,
};

interface NavItem {
  label: string;
  icon: TablerIcon;
  path?: string;
  children?: NavItem[];
}

interface AdminLayoutProps {
  nav: NavItemDescriptor[];
  modules: ResolvedModule[];
  loading: boolean;
}

/** Resolve icon: known name → Tabler component, SVG string → inline wrapper, fallback → IconDashboard */
function resolveIcon(icon?: string): TablerIcon {
  if (!icon) return IconDashboard;
  if (iconMap[icon]) return iconMap[icon];

  // Treat as raw SVG string
  if (icon.includes('<svg')) {
    return ({ size = 16 }: IconProps) => (
      <span
        style={{ width: size, height: size, display: 'inline-flex' }}
        dangerouslySetInnerHTML={{ __html: icon }}
      />
    );
  }

  return IconDashboard;
}

function sortByPosition(items: NavItemDescriptor[]): NavItemDescriptor[] {
  return [...items].sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
}

/** Convert backend nav descriptor to NavItem with resolved icon */
function toNavItem(desc: NavItemDescriptor): NavItem {
  return {
    label: desc.label,
    path: desc.path,
    icon: resolveIcon(desc.icon),
    children: desc.children ? sortByPosition(desc.children).map(toNavItem) : undefined,
  };
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

export function AdminLayout({ nav, modules, loading }: AdminLayoutProps) {
  const [mobileOpened, { toggle: toggleMobile }] = useDisclosure();
  const [desktopOpened, { toggle: toggleDesktop }] = useDisclosure(true);
  const location = useLocation();

  const navItems = sortByPosition(nav).map(toNavItem);

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
          <AdminCoreProvider>
            <Outlet />
          </AdminCoreProvider>
        </Suspense>
      </AppShell.Main>
    </AppShell>
  );
}
