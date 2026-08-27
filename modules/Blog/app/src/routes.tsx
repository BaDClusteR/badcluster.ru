import {Routes, Route} from "react-router";
import BlogPosts from "./Posts";
import BlogPost from "./Post";
import Tags from "./Tags/Tags";
import {Tag} from "./Tag/Tag";
import BlogNotes from "./Notes";
import BlogNote from "./Note";

export function BlogRoutes() {
  return (
    <Routes>
      <Route index element={<BlogPosts/>}/>
      <Route path="new" element={<BlogPost key="post-new"/>}/>
      <Route path=":id" element={<BlogPost key="post-edit"/>}/>
      <Route path="tags" element={<Tags/>}/>
      <Route path="tags/new" element={<Tag key="tag-new"/>}/>
      <Route path="tags/:id" element={<Tag key="tag-edit"/>}/>
      <Route path="notes" element={<BlogNotes/>}/>
      <Route path="notes/new" element={<BlogNote key="note-new"/>}/>
      <Route path="notes/:id" element={<BlogNote key="note-edit"/>}/>
    </Routes>
  );
}
