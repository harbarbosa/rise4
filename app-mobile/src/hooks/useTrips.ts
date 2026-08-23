import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { fetchTrips, type Trip } from "@/services/travelRefundService";

export const tripsQueryKey = ["trips"] as const;

export function useTrips(
  options?: Omit<UseQueryOptions<Trip[], ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<Trip[], ApiError>({
    queryKey: tripsQueryKey,
    queryFn: fetchTrips,
    staleTime: 60_000,
    ...options,
  });
}
