import { createFileRoute } from "@tanstack/react-router";

// Em desenvolvimento, o app conversa com a instalação local do RiseCRM.
// Em produção, configure RISECRM_API_BASE_URL no processo do servidor.
const UPSTREAM_BASE_URL = process.env.RISECRM_API_BASE_URL || "http://rise4.test/index.php";

const CORS_HEADERS = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, PUT, PATCH, DELETE, OPTIONS",
  "Access-Control-Allow-Headers": "Accept, Authorization, Content-Type, authtoken",
  "Access-Control-Max-Age": "86400",
};

function buildUpstreamUrl(request: Request) {
  const incoming = new URL(request.url);
  const upstream = new URL(`${UPSTREAM_BASE_URL}${incoming.pathname}`);
  upstream.search = incoming.search;
  return upstream;
}

function buildHeaders(request: Request) {
  const headers = new Headers();
  headers.set("Accept", request.headers.get("Accept") || "application/json");
  headers.set("User-Agent", "AlfaHP-Mobile/1.0");

  const contentType = request.headers.get("Content-Type");
  // Aceita o token vindo em qualquer um dos nomes que já usamos no app.
  const authToken =
    request.headers.get("authtoken") ||
    request.headers.get("x-authtoken") ||
    request.headers.get("x-auth-token");
  const authorization = request.headers.get("Authorization");
  const bearer = authorization?.toLowerCase().startsWith("bearer ")
    ? authorization.slice(7).trim()
    : null;

  const finalToken = authToken || bearer;

  if (contentType) headers.set("Content-Type", contentType);
  if (finalToken) {
    // Rise CRM exige "authtoken"; também replicamos no Authorization Bearer.
    headers.set("authtoken", finalToken);
    headers.set("Authorization", `Bearer ${finalToken}`);
  }

  console.log("[api-proxy]", request.method, new URL(request.url).pathname, {
    hasAuthToken: !!authToken,
    hasAuthorization: !!authorization,
    forwardedToken: finalToken ? `${finalToken.slice(0, 8)}…` : null,
  });

  return headers;
}

function withCors(response: Response) {
  const headers = new Headers(response.headers);
  headers.delete("content-encoding");
  headers.delete("content-length");
  Object.entries(CORS_HEADERS).forEach(([key, value]) => headers.set(key, value));
  return new Response(response.body, {
    status: response.status,
    statusText: response.statusText,
    headers,
  });
}

async function proxy(request: Request) {
  const upstreamResponse = await fetch(buildUpstreamUrl(request), {
    method: request.method,
    headers: buildHeaders(request),
    body:
      request.method === "GET" || request.method === "HEAD"
        ? undefined
        : await request.arrayBuffer(),
    redirect: "manual",
  });

  if (upstreamResponse.status >= 400) {
    const cloned = upstreamResponse.clone();
    const bodyText = await cloned.text().catch(() => "<no body>");
    console.log("[api-proxy] upstream error", request.method, new URL(request.url).pathname, {
      status: upstreamResponse.status,
      body: bodyText.slice(0, 500),
    });
  }

  return withCors(upstreamResponse);
}

export const Route = createFileRoute("/api/$")({
  server: {
    handlers: {
      OPTIONS: async () => new Response(null, { status: 204, headers: CORS_HEADERS }),
      GET: async ({ request }) => proxy(request),
      POST: async ({ request }) => proxy(request),
      PUT: async ({ request }) => proxy(request),
      PATCH: async ({ request }) => proxy(request),
      DELETE: async ({ request }) => proxy(request),
    },
  },
});
