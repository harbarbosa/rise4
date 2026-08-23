import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { fetchTripExpenses, type Expense } from "@/services/travelRefundService";

export const tripExpensesKey = (tripId: string) => ["trip-expenses", tripId] as const;

export function useTripExpenses(
  tripId: string | undefined,
  options?: Omit<UseQueryOptions<Expense[], ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<Expense[], ApiError>({
    queryKey: tripExpensesKey(tripId ?? ""),
    queryFn: () => fetchTripExpenses(tripId!),
    enabled: !!tripId,
    staleTime: 30_000,
    ...options,
  });
}
