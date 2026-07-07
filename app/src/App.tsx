import {Suspense} from "react";
import {Routes, Route} from "react-router";
import {Loader, Box} from "@mantine/core";
import {LoginPage} from "./pages/Login";
import {AdminLayout} from "./layout/AdminLayout";
import {DashboardPage} from "./pages/Dashboard";
import {useModules} from "./modules/useModules";
import {NotFoundPage} from "./pages/NotFound";
import Comments from "@/pages/Comments";
import Comment from "@/pages/Comment";
import Screenshots from "@/pages/Screenshots";
import Screenshot from "@/pages/Screenshot";
import Photos from "@/pages/Photos";
import Photo from "@/pages/Photo";
import MediaLibrary from "@/pages/MediaLibrary";
import Media from "@/pages/Media";

export function App() {
  const {nav, modules, loading} = useModules();

  return (
    <Routes>
      <Route path="/admin/login" element={<LoginPage/>}/>
      <Route path="/admin" element={<AdminLayout nav={nav} loading={loading}/>}>
        <Route index element={<DashboardPage/>}/>
        <Route key="comments-list" path="comments" element={<Comments/>}/>
        <Route key="comment-edit" path="comments/:id" element={<Comment/>}/>
        <Route key="screenshots-list" path="screenshots" element={<Screenshots/>}/>
        <Route key="screenshot-add" path="screenshots/new" element={<Screenshot/>}/>
        <Route key="screenshot-edit" path="screenshots/:id" element={<Screenshot/>}/>
        <Route key="photos-list" path="photos" element={<Photos/>}/>
        <Route key="photo-add" path="photos/new" element={<Photo/>}/>
        <Route key="photo-edit" path="photos/:id" element={<Photo/>}/>
        <Route key="media-list" path="media" element={<MediaLibrary/>}/>
        <Route key="media-edit" path="media/:id" element={<Media/>}/>
        {modules.map((mod) => (
          <Route
            key={mod.id}
            path={`${mod.path}/*`}
            element={
              <Suspense fallback={
                <Box style={{display: "flex", justifyContent: "center", padding: 48}}>
                  <Loader/>
                </Box>
              }>
                <mod.component/>
              </Suspense>
            }
          />
        ))}
      </Route>
      <Route path="*" element={loading
        ? <Box style={{display: "flex", justifyContent: "center", padding: 48}}><Loader/></Box>
        : <NotFoundPage/>
      }/>
    </Routes>
  );
}
