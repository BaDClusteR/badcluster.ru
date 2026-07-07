import type {FieldDef} from "@admin/types";
import {MediaContext, MediaFile} from "../types";
import MediaDimensions from "./MediaDimensions";
import MediaPreview from "./MediaPreview";
import MediaThumbnails from "./MediaThumbnails";

const FIELDS: FieldDef<MediaFile, MediaContext>[] = [
  {
    type: "group",
    span: "full",
    render: (_form, options) => <MediaPreview url={options.context?.url} mime={options.context?.mime}/>
  },
  {
    type: "group",
    span: "full",
    render: (form) => <MediaDimensions form={form}/>
  },
  {
    name: "alt",
    label: "Alt текст",
    type: "text",
    span: "full"
  },
  {
    type: "group",
    span: "full",
    render: (_form, options) => <MediaThumbnails thumbs={options.context?.thumbs}/>
  }
];

export default FIELDS;