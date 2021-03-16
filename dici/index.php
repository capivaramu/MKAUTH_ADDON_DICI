<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
include('addons.class.php');
include('conexao.php');

// VERIFICA SE O USUARIO ESTA LOGADO --------------------------------------------------------------
session_name('mka');
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['mka_logado'])) exit('Acesso negado...');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="iso-8859-1">
<title>MK - AUTH :: <?php echo $Manifest->{'name'}; ?></title>

<link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
<link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
<link href="../../estilos/jquery-ui.css" rel="stylesheet" type="text/css" media="screen" />

<script src="../../scripts/vue.js"></script>
<script src="../../scripts/jquery.js"></script>
<script src="../../scripts/jquery-ui.js"></script>
<script src="../../scripts/mk-auth.js"></script>

</head>
<body bgcolor="whitesmoke">

<?php include('../../topo.php'); ?>

<table width="100%" align="center">
<tr>
<td><h2 align="center" class="subtitle"> <?php echo $Manifest->{'name'}; ?> </h2></td>
</tr>
</table>

<table width="100%" align="center">
<tr>
<td>
<h6>VERSAO ADDON: <?php echo $Manifest->{'version'}; ?> / AUTOR: <?php echo $Manifest->{'author'}; ?></h6>
<h6>Contribua com o trabalho do desenvolvedor->  PIX chave aleatoria = eb0fd51f-14b4-4994-93c3-f2caa0ff633e</h6>



<form  style="text-align: center" action="index.php" method="GET">
<div >
    <table >
        <tbody>
            <tr  class="column">
                <td style="display: flex;  border: ridge;">
                    <input type="checkbox" name="desativado" value="sim">Selecione para incluir os clientes desativados.<br/>
                    <input type="checkbox" name="bloqueado" value="sim">Selecione para incluir os clientes bloqueados.<br/>
                    <input type="checkbox" name="ngerarsici" value="sim">Selecione para incluir os clientes marcados no cadastro para nao gerar SICI.<br/>
                    <input type="checkbox" name="ngerarnf" value="sim">Selecione para incluir os clientes marcados no cadastro para nao gerar NF.<br/>
                </td>
            </tr>
        </tbody>
    </table >
    </div>
<br/>
    <div>

        <label> Mes de Referencia</label>
            <p>
                <select name="mes">
                    <option value="01">01</option>
                    <option value="02">02</option>
                    <option value="03">03</option>
                    <option value="04">04</option>
                    <option value="05">05</option>
                    <option value="06">06</option>
                    <option value="07">07</option>
                    <option value="08">08</option>
                    <option value="09">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>
            </p><br/>
        </div>
        <div>
        <label> Ano de Referencia</label>
            <p>
                <select name="ano">
                    <option value="2021">2021</option>
                    <option value="2022">2022</option>
                    <option value="2023">2023</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                </select>
            </p>
        </div>
        <div>
            <input type="hidden" name="form_submitted" value="1"/>
            <button  type="submit">Gerar documento </button>
        </div>
    </form>

<?php

if ($_GET['form_submitted']) { // aqui é onde vai decorrer a chamada se houver um *request* POST
 
    $csv_filename = 'dbexport'.time().'.csv';
    $csv_caminho = '/opt/mk-auth/mkfiles/'.$csv_filename.'' ;


    $pdo = new PDO("mysql:host=localhost;dbname=mkradius", "root", "vertrigo"); 

    if (!$pdo) {
        echo "Error: Falha ao conectar-se com o banco de dados MySQL." . PHP_EOL;
        echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }

    $cli_bloqueado="'nao'";
    if(isset($_GET['bloqueado'])){
        $cli_bloqueado= "'nao' or a.bloqueado ='sim' ";
    } 

    $cli_ativado="'s'";
    if(isset($_GET['desativado'])){
        $cli_ativado= "'s' or a.cli_ativado ='n' ";
    } 

    $cli_mes="";
    if(isset($_GET['mes'])){
        $cli_mes= $_GET['mes'];
    } 

    $cli_ano="";
    if(isset($_GET['ano'])){
        $cli_ano= $_GET['ano'];
    } 

    $cli_gerarSici="a.gsici = 1";
    if(isset($_GET['ngerarsici'])){
        $cli_gerarSici= " (a.gsici = 1 or a.gsici = 0) ";
    } 

    $cli_gerarNF="a.geranfe = 'sim'";
    if(isset($_GET['ngerarnf'])){
        $cli_gerarNF= " (a.geranfe = 'sim' or a.geranfe = 'nao') ";
    } 

    $stmt  = $pdo->exec("select 'CNPJ', 'ANO', 'MES', 'COD_IBGE', 'TIPO_CLIENTE', 'TIPO_ATENDIMENTO', 'TIPO_MEIO', 'TIPO_PRODUTO', 'TIPO_TECNOLOGIA', 'VELOCIDADE', 'QT_ACESSOS'
    UNION
    select prov.cnpj, ".$cli_ano.", ".$cli_mes.", a.cidade_ibge COD_IBGE, IF(a.tipo_pessoa=3,'PF','PJ') TIPO_CLIENTE, if(instr(a.tags,'rural') <> 0, 'RURAL', 'URBANO') TIPO_ATENDIMENTO,
    if(p.tecnologia = 'H', 'fibra', if(p.tecnologia='k' or p.tecnologia='D' or p.tecnologia='C','radio', if(p.tecnologia = 'G', 'satelite', if(p.tecnologia = 'M', 'cabo_metalico', if(p.tecnologia = 'J', 'cabo_coaxial', 'cabo_metalico'))))) TIPO_MEIO,
    'internet' TIPO_PRODUTO, 'Ethernet' TIPO_TECNOLOGIA, format(p.veldown,0) VELOCIDADE, count(*) QT_ACESSOS 
    into outfile '".$csv_caminho."'fields terminated by ';' optionally enclosed by '' lines terminated by '\r\n'
    from sis_provedor prov, sis_cliente a inner join sis_plano p on a.plano = p.nome 
    where a.cli_ativado = ".$cli_ativado."
    and a.bloqueado =".$cli_bloqueado."
    and ".$cli_gerarSici."
    and ".$cli_gerarNF."
    and a.cidade_ibge is not null
    and a.plano is not null
    and STR_TO_DATE(a.cadastro, '%d/%m/%Y') <= STR_TO_DATE('30/".$cli_mes."/".$cli_ano."', '%d/%m/%Y')
    group by a.cidade_ibge, a.tipo_pessoa,TIPO_ATENDIMENTO, tipo_meio, p.veldown");

    $result  = $pdo->query("
    select prov.cnpj CNPJ, ".$cli_ano." ANO, ".$cli_mes." MES, a.cidade_ibge COD_IBGE, IF(a.tipo_pessoa=3,'PF','PJ') TIPO_CLIENTE, if(instr(a.tags,'rural') <> 0, 'RURAL', 'URBANO') TIPO_ATENDIMENTO,
    if(p.tecnologia = 'H', 'fibra', if(p.tecnologia='k' or p.tecnologia='D' or p.tecnologia='C','radio', if(p.tecnologia = 'G', 'satelite', if(p.tecnologia = 'M', 'cabo_metalico', if(p.tecnologia = 'J', 'cabo_coaxial', 'cabo_metalico'))))) TIPO_MEIO,
    'internet' TIPO_PRODUTO, 'Ethernet' TIPO_TECNOLOGIA, format(p.veldown,0) VELOCIDADE, count(*) QT_ACESSOS 
    from sis_provedor prov, sis_cliente a inner join sis_plano p on a.plano = p.nome 
    where a.cli_ativado = ".$cli_ativado."
    and a.bloqueado =".$cli_bloqueado."
    and ".$cli_gerarSici."
    and ".$cli_gerarNF."
    and a.cidade_ibge is not null
    and a.plano is not null
    and STR_TO_DATE(a.cadastro, '%d/%m/%Y') <= STR_TO_DATE('30/".$cli_mes."/".$cli_ano."', '%d/%m/%Y')
    group by a.cidade_ibge, a.tipo_pessoa, TIPO_ATENDIMENTO, tipo_meio, p.veldown");

    $resultSucesso  = $pdo->query("
    select a.nome NOME
    from sis_cliente a
    where a.cli_ativado = ".$cli_ativado."
    and a.bloqueado =".$cli_bloqueado."
    and ".$cli_gerarSici."
    and ".$cli_gerarNF."
    and a.cidade_ibge is not null
    and a.plano is not null
    and STR_TO_DATE(a.cadastro, '%d/%m/%Y') <= STR_TO_DATE('30/".$cli_mes."/".$cli_ano."', '%d/%m/%Y')
    order by a.nome");

    ?>
    <br/>

    Relatorio Gerado - Mes de Referencia = <?php echo $cli_mes; ?> e Ano= <?php echo $cli_ano; ?>
    <div style="display: grid;">
        <table>
        <thead>
            <tr class="tab_th">
            <th>CNPJ</th>
            <th>ANO</th>
            <th>MES</th>
            <th>COD_IBGE</th>
            <th>TIPO_CLIENTE</th>
            <th>TIPO_ATENDIMENTO</th>
            <th>TIPO_MEIO</th>
            <th>TIPO_PRODUTO</th>
            <th>TIPO_TECNOLOGIA</th>
            <th>VELOCIDADE</th>
            <th>QT_ACESSOS</th>
            </tr>
        </thead>
        <tbody>
    <?php

    foreach($result as $row){
        ?>
        <tr>
          <td><?php echo $row['CNPJ']; ?></td>
          <td><?php echo $row['ANO']; ?></td>
          <td><?php echo $row['MES']; ?></td>
          <td><?php echo $row['COD_IBGE']; ?></td>
          <td><?php echo $row['TIPO_CLIENTE']; ?></td>
          <td><?php echo $row['TIPO_ATENDIMENTO']; ?></td>
          <td><?php echo $row['TIPO_MEIO']; ?></td>
          <td><?php echo $row['TIPO_PRODUTO']; ?></td>
          <td><?php echo $row['TIPO_TECNOLOGIA']; ?></td>
          <td><?php echo $row['VELOCIDADE']; ?></td>
          <td><?php echo $row['QT_ACESSOS']; ?></td>   
        </tr>
      <?php }

    ?>
        </tbody>
    </table>
    </div>
    <?php   

    ?>
    <div style="text-align: center">
    <br/><br/>
    <a href="<?php echo '/mkfiles/'.$csv_filename; ?>" title="Download File"  style="color:#FF0000;">
    <img src="https://cdn1.solojavirtual.com/arquivos_loja/1579/Fotos/pi_228816.jpg" 
    width="150" height="140"/>
    <br/>
    <b> DOWNLOAD DO ARQUIVO<b></a>
    </div>
    <?php
    
    
    ?>
    <br/>
    Clientes Adicionados no Relatorio
    <div style="display: grid; overflow:scroll; height:150px;">
        <table>
        <thead>
            <tr class="tab_th">
            <th>Nome</th>
            </tr>
        </thead>
        <tbody>
    <?php
foreach($resultSucesso as $row){
    ?>
    <tr>
      <td><?php echo $row['NOME']; ?></td>     
    </tr>
  <?php }

    


}

mysqli_close($pdo);
?>

</td>
</tr>
</table>

<?php include('../../baixo.php'); ?>

</body>
</html>