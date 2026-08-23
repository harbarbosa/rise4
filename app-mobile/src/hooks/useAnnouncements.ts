import { useQuery } from "@tanstack/react-query";
import { api } from "@/services/api";

export interface Announcement {
  id: string | number;
  title: string;
  description: string;
  start_date: string;
  end_date: string;
  share_with: string;
  read_by: string;
  created_by: string | number;
  created_at: string;
  updated_at?: string | null;
  files?: string;
  deleted: string | number;
}

interface AnnouncementsResponse {
  status: boolean;
  data: Announcement[];
}

export function useAnnouncements() {
  return useQuery<Announcement[]>({
    queryKey: ["announcements"],
    queryFn: async () => {
      const { data } = await api.get<AnnouncementsResponse>("/api/announcements");
      return data?.data ?? [];
    },
    staleTime: 60_000,
  });
}

export function useAnnouncement(id: string) {
  const { data, ...rest } = useAnnouncements();
  const announcement = (data ?? []).find((a) => String(a.id) === String(id)) ?? null;
  return { data: announcement, ...rest };
}
