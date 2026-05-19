import { Routes, Route } from 'react-router';
import BlogPosts from './Posts/Posts';
import { BlogPost } from './Post/Post';
import Tags from "./Tags/Tags";
import {Tag} from "./Tag/Tag";

export function BlogRoutes() {
  return (
    <Routes>
      <Route index element={<BlogPosts />} />
      <Route path="new" element={<BlogPost key="post-new" />} />
      <Route path=":id" element={<BlogPost key="post-edit" />} />
      <Route path="tags" element={<Tags />} />
      <Route path="tags/new" element={<Tag key="tag-new" />} />
      <Route path="tags/:id" element={<Tag key="tag-edit" />} />
    </Routes>
  );
}
