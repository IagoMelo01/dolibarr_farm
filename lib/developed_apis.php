<?php

public function api_rec_adubo($id_obj){

// Parâmetros da consulta
$idCultura = 1;
$idCultivar = 1;
$cad = 1;
$codigoIBGE = 1;
$dataPlantio = 1;
$expectativaProdutividade = 1;
$nome_arquivo = 'rec_adubo_' . time();
$rec_adubo = null;

echo $nome_arquivo;

// URL base da API
$url_base = "https://api.cnptia.embrapa.br/agritec/v1/produtividade";


// recupera dados do objeto atual
$sql = "SELECT ";
$sql .= $this->getFieldList('t');
$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
$sql .= " WHERE t.rowid = ".$id_obj;

$resql = $this->db->query($sql);

$obj = $this->db->fetch_object($resql);
$rec_adubo = new self($this->db);
$rec_adubo->setVarsFromFetchObj($obj);

$this->db->free($resql);


// recupera dados do projeto/plano de plantio
$sql = "SELECT fk_cultura, fk_variedade, data_plantio FROM " . MAIN_DB_PREFIX . "projet_extrafields WHERE fk_object = ". $rec_adubo->fk_project ;

$resql = $this->db->query($sql);
$data_project = $this->db->fetch_object($resql);

$this->db->free($resql);

// recupera id CULTURA
$sql = "SELECT embrapaid FROM ";
$sql .= MAIN_DB_PREFIX;
$sql .= "safra_cultura WHERE rowid = ";
$sql .= $data_project->fk_cultura;

$resql = $this->db->query($sql);
$obj_cultura = $this->db->fetch_object($resql);

// recupera id CULTIVAR
$sql = "SELECT idCultivar FROM ";
$sql .= MAIN_DB_PREFIX;
$sql .= "safra_variedade WHERE rowid = ";
$sql .= $data_project->fk_variedade;

$resql = $this->db->query($sql);
$obj_cultivar = $this->db->fetch_object($resql);

// recupera IBGE MUNICIPIO
$sql = "SELECT codigoIBGE FROM ";
$sql .= MAIN_DB_PREFIX;
$sql .= "safra_municipio WHERE rowid = ";
$sql .= $rec_adubo->fk_municipio;

$resql = $this->db->query($sql);
$obj_municipio = $this->db->fetch_object($resql);


// Preparação dos dados
$idCultura = $obj_cultura->embrapaid;
$idCultivar = $obj_cultivar->idCultivar;
$cad = $rec_adubo->cap_agua;
$codigoIBGE = $obj_municipio->codigoIBGE;
$dataPlantio = $data_project->data_plantio;
$expectativaProdutividade = $rec_adubo->expect_prod;

print_r($data_project);



// Construir a URL completa com os parâmetros
$url = $url_base . "?idCultura=$idCultura&idCultivar=$idCultivar&cad=$cad&codigoIBGE=$codigoIBGE&dataPlantio=$dataPlantio&expectativaProdutividade=$expectativaProdutividade";

echo "<br>" . $url . "<br>"; 
// Token de autorização
$token = "c0a1d87a-0123-36b1-8fe6-ebb928bf1b63";

// Defina o caminho para salvar o arquivo JSON
$caminho_arquivo = DOL_DOCUMENT_ROOT."/custom/safra/json/adubacao/". $nome_arquivo .".json";
$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET `arquivo_json`='".$caminho_arquivo."' WHERE rowid = $id_obj";
$this->db->query($sql);
echo "<br><b>SQL: </b>" . $sql . "<br>";

// Configuração da solicitação CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: application/json',
    'Authorization: Bearer ' . $token
));

// Executar a solicitação
$response = curl_exec($ch);

// Verificar se ocorreu algum erro
if(curl_errno($ch)){
    echo 'Erro ao fazer a solicitação CURL: ' . curl_error($ch);
    // Trate o erro adequadamente
}

// Fechar a conexão CURL
curl_close($ch);

// Salvar a resposta JSON em um arquivo
file_put_contents($caminho_arquivo, $response);

// Verificar se o arquivo foi salvo com sucesso
if (file_exists($caminho_arquivo)) {
    echo "Arquivo salvo com sucesso em: $caminho_arquivo";
} else {
    echo "Erro ao salvar o arquivo.";
    // Trate o erro adequadamente
}

return $response;
}