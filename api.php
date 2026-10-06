<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$arquivo = 'basekaisen.json';

function lerDados($arquivo) {
    if (!file_exists($arquivo)) return [];
    $conteudo = file_get_contents($arquivo);
    return json_decode($conteudo, true) ?? [];
}

function salvarDados($arquivo, $dados) {
    file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$metodo = $_SERVER['REQUEST_METHOD'];
$dados = json_decode(file_get_contents('php://input'), true);
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$lista = lerDados($arquivo);

switch ($metodo) {
    case 'GET':
        if ($id !== null) {
            foreach ($lista as $item) {
                if ($item['id'] === $id) {
                    echo json_encode($item);
                    exit;
                }
            }
            http_response_code(404);
            echo json_encode(["erro" => "Personagem não encontrado."]);
        } else {
            echo json_encode($lista);
        }
        break;

    case 'POST':
        if (!isset($dados['nome']) || !isset($dados['cla']) || !isset($dados['tecnica']) || !isset($dados['idade'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Campos 'nome', 'cla', 'tecnica' e 'idade' são obrigatórios."]);
            exit;
        }
        $novoId = count($lista) > 0 ? max(array_column($lista, 'id')) + 1 : 1;
        $novoItem = [
            "id" => $novoId,
            "nome" => $dados['nome'],
            "cla" => $dados['cla'],
            "tecnica" => $dados['tecnica'],
            "idade" => (int)$dados['idade']
        ];
        $lista[] = $novoItem;
        salvarDados($arquivo, $lista);
        http_response_code(201);
        echo json_encode($novoItem);
        break;

    case 'PUT':
        if ($id === null) {
            http_response_code(400);
            echo json_encode(["erro" => "Informe o ID para atualizar (PUT)."]);
            exit;
        }
        $encontrado = false;
        foreach ($lista as &$item) {
            if ($item['id'] === $id) {
                if (!isset($dados['nome']) || !isset($dados['cla']) || !isset($dados['tecnica']) || !isset($dados['idade'])) {
                    http_response_code(400);
                    echo json_encode(["erro" => "Todos os campos são obrigatórios no PUT."]);
                    exit;
                }
                $item['nome'] = $dados['nome'];
                $item['cla'] = $dados['cla'];
                $item['tecnica'] = $dados['tecnica'];
                $item['idade'] = (int)$dados['idade'];
                $encontrado = true;
                break;
            }
        }
        if ($encontrado) {
            salvarDados($arquivo, $lista);
            echo json_encode(["mensagem" => "Personagem atualizado com sucesso."]);
        } else {
            http_response_code(404);
            echo json_encode(["erro" => "Personagem não encontrado."]);
        }
        break;

    case 'PATCH':
        if ($id === null) {
            http_response_code(400);
            echo json_encode(["erro" => "Informe o ID para editar (PATCH)."]);
            exit;
        }
        $encontrado = false;
        foreach ($lista as &$item) {
            if ($item['id'] === $id) {
                if (isset($dados['nome'])) $item['nome'] = $dados['nome'];
                if (isset($dados['cla'])) $item['cla'] = $dados['cla'];
                if (isset($dados['tecnica'])) $item['tecnica'] = $dados['tecnica'];
                if (isset($dados['idade'])) $item['idade'] = (int)$dados['idade'];
                $encontrado = true;
                break;
            }
        }
        if ($encontrado) {
            salvarDados($arquivo, $lista);
            echo json_encode(["mensagem" => "Personagem alterado parcialmente com sucesso."]);
        } else {
            http_response_code(404);
            echo json_encode(["erro" => "Personagem não encontrado."]);
        }
        break;

    case 'DELETE':
        if ($id === null) {
            http_response_code(400);
            echo json_encode(["erro" => "Informe o ID para excluir."]);
            exit;
        }
        $novaLista = array_filter($lista, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        if (count($novaLista) === count($lista)) {
            http_response_code(404);
            echo json_encode(["erro" => "Personagem não encontrado."]);
        } else {
            salvarDados($arquivo, array_values($novaLista));
            echo json_encode(["mensagem" => "Personagem removido com sucesso."]);
        }
        break;
}