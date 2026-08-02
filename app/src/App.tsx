import {Suspense} from "react";
import {Routes, Route, Navigate} from "react-router";
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
import Redirects from "@/pages/Redirects";
import Redirect from "@/pages/Redirect";
import Facts from "@/pages/Facts";
import Fact from "@/pages/Fact";
import PhotoTags from "@/pages/PhotoTags";
import PhotoTag from "@/pages/PhotoTag";
import PulseItems from "@/pages/PulseItems";
import PulseItem from "@/pages/PulseItem";
import Settings from "@/pages/Settings";

export function App() {
  const {nav, modules, loading} = useModules();

  return (
    <Routes>
      <Route path="/login/notbot" element={<LoginPage/>}/>
      <Route path="/admin" element={<AdminLayout nav={nav} loading={loading}/>}>
        <Route index element={<DashboardPage/>}/>
        <Route key="comments-list" path="comments" element={<Comments/>}/>
        <Route key="comment-edit" path="comments/:id" element={<Comment/>}/>
        <Route key="screenshots-list" path="screenshots" element={<Screenshots/>}/>
        <Route key="screenshot-add" path="screenshots/new" element={<Screenshot/>}/>
        <Route key="screenshot-edit" path="screenshots/:id" element={<Screenshot/>}/>
        <Route key="photos-list" path="photos" element={<Photos/>}/>
        <Route key="photo-tags-list" path="photo-tags" element={<PhotoTags/>}/>
        <Route key="photo-tag-add" path="photo-tags/new" element={<PhotoTag/>}/>
        <Route key="photo-tag-edit" path="photo-tags/:id" element={<PhotoTag/>}/>
        <Route key="photo-add" path="photos/new" element={<Photo/>}/>
        <Route key="photo-edit" path="photos/:id" element={<Photo/>}/>
        <Route key="media-list" path="media" element={<MediaLibrary/>}/>
        <Route key="media-edit" path="media/:id" element={<Media/>}/>
        <Route key="redirects-list" path="redirects" element={<Redirects/>}/>
        <Route key="redirect-add" path="redirects/new" element={<Redirect/>}/>
        <Route key="redirect-edit" path="redirects/:id" element={<Redirect/>}/>
        <Route key="facts-list" path="facts" element={<Facts/>}/>
        <Route key="fact-add" path="facts/new" element={<Fact/>}/>
        <Route key="fact-edit" path="facts/:id" element={<Fact/>}/>
        <Route key="pulse-list" path="pulse" element={<PulseItems/>}/>
        <Route key="pulse-item-index" path="pulse_item" element={<Navigate to="/admin/pulse" replace/>}/>
        <Route key="pulse-item-add" path="pulse_item/new" element={<PulseItem/>}/>
        <Route key="pulse-item-edit" path="pulse_item/:id" element={<PulseItem/>}/>
        <Route key="settings" path="settings" element={<Settings/>}/>
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
