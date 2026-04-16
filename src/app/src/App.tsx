import { Routes, Route, Navigate } from 'react-router';
import { LoginPage } from './pages/Login';
import { AdminLayout } from './layout/AdminLayout';
import { DashboardPage } from './pages/Dashboard';
import { PageEdit } from './pages/Pages/Edit';
import { useModules } from './modules/useModules';
import BlogPosts from "@/pages/Blog/Posts/Posts.tsx";

export function App() {
  const { modules, loading } = useModules();

  return (
    <Routes>
      <Route path="/admin/login" element={<LoginPage />} />
      <Route path="/admin" element={<AdminLayout modules={modules} loading={loading} />}>
        <Route index element={<DashboardPage />} />
        <Route path="pages" element={<BlogPosts />} />
        <Route path="pages/:id" element={<PageEdit />} />
        {modules.map((mod) => (
          <Route key={mod.path} path={mod.path} element={<mod.component />} />
        ))}
      </Route>
      <Route path="*" element={<Navigate to="/admin" replace />} />
    </Routes>
  );
}
