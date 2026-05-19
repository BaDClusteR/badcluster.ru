import { Suspense } from 'react';
import { Routes, Route } from 'react-router';
import { Loader, Box } from '@mantine/core';
import { LoginPage } from './pages/Login';
import { AdminLayout } from './layout/AdminLayout';
import { DashboardPage } from './pages/Dashboard';
import { useModules } from './modules/useModules';
import { NotFoundPage } from './pages/NotFound';

export function App() {
  const { nav, modules, loading } = useModules();

  return (
    <Routes>
      <Route path="/admin/login" element={<LoginPage />} />
      <Route path="/admin" element={<AdminLayout nav={nav} modules={modules} loading={loading} />}>
        <Route index element={<DashboardPage />} />
        {modules.map((mod) => (
            <Route
              key={mod.id}
              path={`${mod.path}/*`}
              element={
                <Suspense fallback={
                  <Box style={{ display: 'flex', justifyContent: 'center', padding: 48 }}>
                    <Loader />
                  </Box>
                }>
                  <mod.component />
                </Suspense>
              }
            />
        ))}
      </Route>
      <Route path="*" element={loading
        ? <Box style={{ display: 'flex', justifyContent: 'center', padding: 48 }}><Loader /></Box>
        : <NotFoundPage />
      } />
    </Routes>
  );
}
