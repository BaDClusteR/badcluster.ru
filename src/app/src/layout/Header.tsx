import classes from "@/layout/Header.module.css";
import {ActionIcon, AppShell, Burger, Group, useMantineColorScheme} from "@mantine/core";
import {IconLogout, IconMoon, IconSun} from "@tabler/icons-react";
import {notify} from "@/lib/notify.ts";
import {useNavigate} from "react-router";

export default function Header(
  {
    mobileOpened,
    desktopOpened,
    toggleMobile,
    toggleDesktop
  }: {
    mobileOpened: boolean,
    desktopOpened: boolean,
    toggleMobile: () => void,
    toggleDesktop: () => void,
  }
) {
  const { colorScheme, toggleColorScheme } = useMantineColorScheme({
    keepTransitions: true
  });
  const navigate = useNavigate();

  async function handleLogout() {
    try {
      await fetch('/api/admin/logout', { method: 'POST' });
    } catch {
      // Network error — still log out on the client
    }
    notify.success('Signed out');
    navigate('/admin/login', { replace: true });
  }

  return <AppShell.Header className={classes.header}>
    <Group h="100%" px="md" justify="space-between">
      <Group>
        <Burger
          classNames={{burger: classes.headerBurger}}
          opened={mobileOpened}
          onClick={toggleMobile}
          hiddenFrom="sm"
          size="sm"
        />
        <Burger
          classNames={{burger: classes.headerBurger}}
          opened={desktopOpened}
          onClick={toggleDesktop}
          visibleFrom="sm"
          size="sm"
        />
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
}
