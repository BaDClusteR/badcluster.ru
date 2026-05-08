import { Routes, Route } from 'react-router';
import BlogPosts from './Posts/Posts';
import { BlogPost } from './Post/Post';

export function BlogRoutes() {
  return (
    <Routes>
      <Route index element={<BlogPosts />} />
      <Route path=":id" element={<BlogPost />} />
    </Routes>
  );
}