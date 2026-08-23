import { useQuery } from "@tanstack/react-query";
import { useAuth } from "@/contexts/AuthContext";
import { ApiError } from "@/services/api";
import {
  fetchCurrentTeamMember,
  type TeamMemberProfile,
} from "@/services/teamMemberService";

/**
 * Carrega o perfil completo do usuário autenticado.
 * Combina dados do login (AuthUser) com os campos extras retornados por
 * GET /api/team_members/{id} (telefone, whatsapp, contratação, etc.).
 */
export function useCurrentTeamMember() {
  const { user } = useAuth();

  return useQuery<TeamMemberProfile | null, ApiError>({
    queryKey: ["current-team-member", user?.id, user?.email],
    enabled: !!user?.id,
    staleTime: 60_000,
    queryFn: async () => {
      if (!user?.id) return null;
      const remote = await fetchCurrentTeamMember({
        id: user.id,
        email: user.email,
      });

      // Mescla: backend (mais completo) sobrepõe campos básicos do auth,
      // mas mantém fallback nos dados de login quando ausentes.
      const base: TeamMemberProfile = {
        id: user.id,
        nome: user.nome || "",
        email: user.email || "",
        cargo: user.cargo || undefined,
        roleTitle: user.role_title ?? undefined,
        avatarUrl: user.avatar_url ?? null,
      };

      if (!remote) return base;

      return {
        ...base,
        ...Object.fromEntries(
          Object.entries(remote).filter(
            ([, v]) => v !== undefined && v !== null && v !== "",
          ),
        ),
      } as TeamMemberProfile;
    },
  });
}
