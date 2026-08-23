import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { fetchTimelogPhotos, type TimelogPhoto } from "@/services/photoService";

export const timelogPhotosQueryKey = (timelogId: string) =>
  ["timelog-photos", timelogId] as const;

export function useTimelogPhotos(
  timelogId: string | undefined,
  options?: Omit<UseQueryOptions<TimelogPhoto[], ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<TimelogPhoto[], ApiError>({
    queryKey: timelogPhotosQueryKey(timelogId ?? ""),
    queryFn: () => fetchTimelogPhotos(timelogId!),
    enabled: Boolean(timelogId),
    staleTime: 60_000,
    ...options,
  });
}
