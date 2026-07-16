import {useState} from "react";
import {useNavigate} from "react-router";
import {
  Button,
  PasswordInput,
  Stack,
  TextInput
} from "@mantine/core";
import {useForm} from "@mantine/form";
import {notify} from "@/lib/notify";
import classes from "./Login.module.css";

export function LoginPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);

  const form = useForm({
    initialValues: {login: "", password: ""},
    validate: {
      login: (v) => (v.trim().length === 0 ? "Enter login" : null),
      password: (v) => (v.length === 0 ? "Enter password" : null)
    }
  });

  async function handleSubmit(values: typeof form.values) {
    setLoading(true);
    try {
      const res = await fetch("/admin/api/auth", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(values)
      });
      const data = await res.json().catch(() => null);

      if (!res.ok) {
        // noinspection ExceptionCaughtLocallyJS
        throw new Error(data?.error ?? "Login failed");
      }

      // The server sets the HttpOnly "token" cookie on this response.
      notify.success("Welcome back");
      navigate("/admin");
    } catch (err) {
      notify.error("Error", err instanceof Error ? err.message : "Login failed");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className={classes.screen}>
      <div className={classes.card}>
        <Stack gap="lg">
          <div className={classes.logo}>
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" fill="none">
              <rect x="4" y="4" width="24" height="24" rx="8" fill-opacity="0.2" fill="var(--color-text)"></rect>
              <rect x="36" y="4" width="24" height="24" rx="8" fill-opacity="0.2" fill="var(--color-text)"></rect>
              <rect x="4" y="36" width="24" height="24" rx="8" fill-opacity="0.2" fill="var(--color-text)"></rect>
              <rect x="36" y="36" width="24" height="24" rx="8" fill="#22c55e"></rect>
            </svg>
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
                {...form.getInputProps("login")}
              />
              <PasswordInput
                label="Password"
                placeholder="********"
                {...form.getInputProps("password")}
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
