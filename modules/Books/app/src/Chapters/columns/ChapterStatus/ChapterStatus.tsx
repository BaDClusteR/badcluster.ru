import {useAdminCore} from "../../../admin/useAdminCore";

export default function ChapterStatus(props: { published: boolean }) {
  const {BadgeGreen, BadgeGray} = useAdminCore();

  return props.published
    ? <BadgeGreen>Опубликована</BadgeGreen>
    : <BadgeGray>Черновик</BadgeGray>;
}
