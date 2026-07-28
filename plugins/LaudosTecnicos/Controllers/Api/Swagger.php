<?php

/**
 * OpenAPI/Swagger Documentation
 * Laudos Técnicos API v1
 * 
 * @api Laudos Técnicos
 * @version 1.0.0
 */

namespace LaudosTecnicos\Controllers\Api;

class Swagger extends \App\Controllers\BaseController
{
    public function index()
    {
        $spec = array(
            'openapi' => '3.0.0',
            'info' => array(
                'title' => 'API Laudos Técnicos',
                'description' => 'API REST para aplicativo mobile de Laudos Técnicos. Inclui gestão de laudos, inspeções, checklists, medições, fotografias e não conformidades.',
                'version' => '1.0.0',
                'contact' => array(
                    'name' => 'RISE CRM',
                    'email' => 'suporte@rise.com.br'
                ),
                'license' => array(
                    'name' => 'Proprietário'
                )
            ),
            'servers' => array(
                array(
                    'url' => base_url('api/laudos/v1'),
                    'description' => 'Servidor de Produção'
                )
            ),
            'components' => array(
                'securitySchemes' => array(
                    'bearerAuth' => array(
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ),
                    'apiKeyHeader' => array(
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key'
                    ),
                    'deviceHeader' => array(
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-Device-UUID'
                    )
                ),
                'schemas' => array(
                    'Laudo' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array('type' => 'integer'),
                            'laudo_number' => array('type' => 'string'),
                            'title' => array('type' => 'string'),
                            'client_id' => array('type' => 'integer'),
                            'status' => array('type' => 'string'),
                            'created_at' => array('type' => 'string', 'format' => 'date-time')
                        )
                    ),
                    'Inspection' => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array('type' => 'integer'),
                            'code' => array('type' => 'string'),
                            'laudo_id' => array('type' => 'integer'),
                            'scheduled_date' => array('type' => 'string', 'format' => 'date'),
                            'scheduled_time' => array('type' => 'string'),
                            'status' => array('type' => 'string'),
                            'checkin_at' => array('type' => 'string', 'format' => 'date-time')
                        )
                    ),
                    'ChecklistAnswer' => array(
                        'type' => 'object',
                        'properties' => array(
                            'item_id' => array('type' => 'integer'),
                            'response' => array('type' => 'string', 'enum' => array('Conforme', 'Não conforme', 'N/A')),
                            'observation' => array('type' => 'string')
                        )
                    ),
                    'Error' => array(
                        'type' => 'object',
                        'properties' => array(
                            'success' => array('type' => 'boolean', 'example' => false),
                            'error' => array('type' => 'string')
                        )
                    ),
                    'AuthResponse' => array(
                        'type' => 'object',
                        'properties' => array(
                            'success' => array('type' => 'boolean', 'example' => true),
                            'data' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'access_token' => array('type' => 'string'),
                                    'refresh_token' => array('type' => 'string'),
                                    'expires_in' => array('type' => 'integer'),
                                    'user' => array('type' => 'object')
                                )
                            )
                        )
                    ),
                    'PaginatedResponse' => array(
                        'type' => 'object',
                        'properties' => array(
                            'success' => array('type' => 'boolean'),
                            'data' => array('type' => 'array'),
                            'pagination' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'page' => array('type' => 'integer'),
                                    'limit' => array('type' => 'integer'),
                                    'total' => array('type' => 'integer'),
                                    'pages' => array('type' => 'integer')
                                )
                            )
                        )
                    )
                )
            ),
            'security' => array(
                array('bearerAuth' => array()),
                array('apiKeyHeader' => array())
            ),
            'paths' => array(
                // Autenticação
                '/auth/login' => array(
                    'post' => array(
                        'summary' => 'Login de usuário',
                        'description' => 'Autentica usuário e retorna tokens de acesso',
                        'tags' => array('Autenticação'),
                        'requestBody' => array(
                            'required' => true,
                            'content' => array(
                                'application/x-www-form-urlencoded' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'required' => array('email', 'password'),
                                        'properties' => array(
                                            'email' => array('type' => 'string', 'format' => 'email'),
                                            'password' => array('type' => 'string', 'format' => 'password')
                                        )
                                    )
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array(
                                'description' => 'Login bem-sucedido',
                                'content' => array(
                                    'application/json' => array(
                                        'schema' => array('$ref' => '#/components/schemas/AuthResponse')
                                    )
                                )
                            ),
                            '401' => array(
                                'description' => 'Credenciais inválidas',
                                'content' => array(
                                    'application/json' => array(
                                        'schema' => array('$ref' => '#/components/schemas/Error')
                                    )
                                )
                            )
                        )
                    )
                ),
                '/auth/refresh' => array(
                    'post' => array(
                        'summary' => 'Atualizar token',
                        'description' => 'Renova tokens de acesso usando refresh token',
                        'tags' => array('Autenticação'),
                        'requestBody' => array(
                            'required' => true,
                            'content' => array(
                                'application/x-www-form-urlencoded' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'required' => array('refresh_token'),
                                        'properties' => array(
                                            'refresh_token' => array('type' => 'string')
                                        )
                                    )
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Tokens atualizados'),
                            '401' => array('description' => 'Refresh token inválido')
                        )
                    )
                ),
                '/auth/logout' => array(
                    'post' => array(
                        'summary' => 'Logout',
                        'description' => 'Revoga tokens do dispositivo',
                        'tags' => array('Autenticação'),
                        'responses' => array(
                            '200' => array('description' => 'Logout realizado')
                        )
                    )
                ),
                
                // Laudos
                '/laudos' => array(
                    'get' => array(
                        'summary' => 'Listar laudos',
                        'description' => 'Retorna lista paginada de laudos',
                        'tags' => array('Laudos'),
                        'parameters' => array(
                            array('name' => 'page', 'in' => 'query', 'schema' => array('type' => 'integer', 'default' => 1)),
                            array('name' => 'limit', 'in' => 'query', 'schema' => array('type' => 'integer', 'default' => 20)),
                            array('name' => 'status', 'in' => 'query', 'schema' => array('type' => 'string'))
                        ),
                        'responses' => array(
                            '200' => array(
                                'description' => 'Lista de laudos',
                                'content' => array(
                                    'application/json' => array(
                                        'schema' => array('$ref' => '#/components/schemas/PaginatedResponse')
                                    )
                                )
                            )
                        )
                    )
                ),
                '/laudos/{id}' => array(
                    'get' => array(
                        'summary' => 'Consultar laudo',
                        'description' => 'Retorna detalhes de um laudo específico',
                        'tags' => array('Laudos'),
                        'parameters' => array(
                            array('name' => 'id', 'in' => 'path', 'required' => true, 'schema' => array('type' => 'integer'))
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Laudo encontrado'),
                            '404' => array('description' => 'Laudo não encontrado')
                        )
                    )
                ),
                
                // Inspeções
                '/inspections' => array(
                    'get' => array(
                        'summary' => 'Listar inspeções',
                        'description' => 'Retorna inspeções atribuídas ao usuário',
                        'tags' => array('Inspeções'),
                        'responses' => array(
                            '200' => array('description' => 'Lista de inspeções')
                        )
                    )
                ),
                '/inspections/{id}/checkin' => array(
                    'post' => array(
                        'summary' => 'Check-in',
                        'description' => 'Registra check-in com coordenadas GPS',
                        'tags' => array('Inspeções'),
                        'parameters' => array(
                            array('name' => 'id', 'in' => 'path', 'required' => true, 'schema' => array('type' => 'integer'))
                        ),
                        'requestBody' => array(
                            'content' => array(
                                'application/x-www-form-urlencoded' => array(
                                    'schema' => array(
                                        'properties' => array(
                                            'lat' => array('type' => 'number'),
                                            'lng' => array('type' => 'number')
                                        )
                                    )
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Check-in realizado')
                        )
                    )
                ),
                
                // Checklists
                '/checklists/{laudo_id}' => array(
                    'get' => array(
                        'summary' => 'Baixar checklists',
                        'description' => 'Retorna checklists de um laudo',
                        'tags' => array('Checklists'),
                        'parameters' => array(
                            array('name' => 'laudo_id', 'in' => 'path', 'required' => true, 'schema' => array('type' => 'integer'))
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Checklists retornados')
                        )
                    )
                ),
                '/checklists/{laudo_id}/answers' => array(
                    'post' => array(
                        'summary' => 'Enviar respostas',
                        'description' => 'Envia respostas dos checklists',
                        'tags' => array('Checklists'),
                        'parameters' => array(
                            array('name' => 'laudo_id', 'in' => 'path', 'required' => true, 'schema' => array('type' => 'integer'))
                        ),
                        'requestBody' => array(
                            'required' => true,
                            'content' => array(
                                'application/x-www-form-urlencoded' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'answers' => array('type' => 'array')
                                        )
                                    )
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Respostas salvas')
                        )
                    )
                ),
                
                // Fotografias
                '/photos/upload' => array(
                    'post' => array(
                        'summary' => 'Enviar foto',
                        'description' => 'Envia fotografia com metadados',
                        'tags' => array('Fotografias'),
                        'requestBody' => array(
                            'required' => true,
                            'content' => array(
                                'multipart/form-data' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'required' => array('photo', 'laudo_id'),
                                        'properties' => array(
                                            'photo' => array('type' => 'string', 'format' => 'binary'),
                                            'laudo_id' => array('type' => 'integer'),
                                            'inspection_id' => array('type' => 'integer'),
                                            'lat' => array('type' => 'number'),
                                            'lng' => array('type' => 'number'),
                                            'caption' => array('type' => 'string')
                                        )
                                    )
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Foto enviada'),
                            '400' => array('description' => 'Erro no envio')
                        )
                    )
                ),
                
                // Não Conformidades
                '/nonconformities' => array(
                    'post' => array(
                        'summary' => 'Criar NC',
                        'description' => 'Cria nova não conformidade',
                        'tags' => array('Não Conformidades'),
                        'requestBody' => array(
                            'required' => true,
                            'content' => array(
                                'application/x-www-form-urlencoded' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'required' => array('title', 'laudo_id'),
                                        'properties' => array(
                                            'title' => array('type' => 'string'),
                                            'description' => array('type' => 'string'),
                                            'laudo_id' => array('type' => 'integer'),
                                            'classification' => array('type' => 'string'),
                                            'probability' => array('type' => 'integer'),
                                            'impact' => array('type' => 'integer')
                                        )
                                    )
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array('description' => 'NC criada')
                        )
                    )
                ),
                
                // Sincronização
                '/sync/changes' => array(
                    'get' => array(
                        'summary' => 'Obter alterações',
                        'description' => 'Retorna alterações desde última sincronização',
                        'tags' => array('Sincronização'),
                        'parameters' => array(
                            array('name' => 'since', 'in' => 'query', 'schema' => array('type' => 'string', 'format' => 'date-time'))
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Alterações retornadas')
                        )
                    )
                ),
                '/sync/push' => array(
                    'post' => array(
                        'summary' => 'Enviar alterações',
                        'description' => 'Envia alterações do dispositivo para o servidor',
                        'tags' => array('Sincronização'),
                        'requestBody' => array(
                            'required' => true,
                            'content' => array(
                                'application/x-www-form-urlencoded' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'changes' => array('type' => 'array')
                                        )
                                    )
                                )
                            )
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Alterações processadas')
                        )
                    )
                ),
                
                // Versões
                '/versions/{laudo_id}' => array(
                    'get' => array(
                        'summary' => 'Listar versões',
                        'description' => 'Retorna histórico de versões de um laudo',
                        'tags' => array('Versionamento'),
                        'parameters' => array(
                            array('name' => 'laudo_id', 'in' => 'path', 'required' => true, 'schema' => array('type' => 'integer'))
                        ),
                        'responses' => array(
                            '200' => array('description' => 'Versões retornadas')
                        )
                    )
                )
            )
        );

        return $this->response->setJSON($spec);
    }
    
    public function ui()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>API Laudos Técnicos - Swagger</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUI({
            url: "' . base_url('api/laudos/swagger') . '",
            dom_id: "#swagger-ui"
        });
    </script>
</body>
</html>';
    }
}