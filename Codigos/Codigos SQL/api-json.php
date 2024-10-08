<?php 
    //A turma B nao sabia usar isso, conversei com o um colega da Turma A e ele falou que precisa disso para retornar o resultado em JSON
    header("Acess-Control-Allowo-Origin:: *");

    $oCon = new PDO('mysql:host=localhost;dbname=biblioteca','root', '');
    // $oCon = new PDO('mysql:host=localhost;dbname=biblioteca','Aluno02-B', 'Aluno02.2DS');  *conexão com o servidor da escola

    var_dump($oCon);

    function fnMostrarLivros()
    {
        global $oCon;
        // deixando a variavel 'global' para reutilizar ela em outros escopos


        $cSQL="SELECT acervo.codigo, acervo.nome, CONCAT(SUBSTRING_INDEX(autor.nome, ' ', -1), ', ', RTRIM(REPLACE(autor.nome, SUBSTRING_INDEX(autor.nome, ' ', -1), ''))) Autor FROM acervo INNER JOIN autor ON acervo.autor = autor.codigo ORDER BY RAND() LIMIT 10";
        // consulta
        

        $oRes=$oCon->query($cSQL, PDO::FETCH_ASSOC)->fetchAll();
        // executando a consulta


        // fazendo uma tabela: APENAS PARA TESTAR SE ESTA FUNCIONANDO
        echo '<table>
                <thead>
                    <tr>
                        <th> Codigo </th>
                        <th> Livro </th>
                        <th> Autor </th>
                    </tr>
                </thead>
            <tbody>';

        // print_r($oRes); saida de Debug

        // PARA CADA ELEMENTO DO $oRes, indice $oReg, Valoresna variavel $oLinha
        foreach($oRes as $oReg => $oLinha)
        {
            // print_r($oLinha); saida de debug

            echo '<tr>';
            // Variavel $oLinha e um array, entao refazemos o foreach
            foreach($oLinha as $oCampo => $oValor){
                
                echo('<td>'.$oValor.'</td>');
                
            }
            echo '</tr>';
        }
        // fechou o corpo da tabela e mesma
        echo '</tbody>
            </table>';

        // funcao retornando o resultado da consulta em JSON 
        return json_encode($oRes);
    }
    // EXECUTA A BENDITA
    fnMostrarLivros();



    echo '<br>';




    function fnLivrosParecidos(int $oCod)
    {
        // Mesma coisa, nao vou comentar de novo em coisas que se repetem
        global $oCon;

        $cSQL="SELECT livro.nome FROM acervo livro JOIN (SELECT * FROM acervo WHERE acervo.codigo = $oCod) tbl ON tbl.autor = livro.autor
        UNION
        SELECT livro.nome FROM acervo livro JOIN (SELECT * FROM acervo WHERE acervo.codigo = $oCod) tbl ON tbl.genero = livro.genero
        UNION
        SELECT livro.nome FROM acervo livro JOIN (SELECT * FROM acervo WHERE acervo.codigo = $oCod) tbl ON tbl.editora = livro.editora LIMIT 3";
        // Nesse aqui concatenei o valor do parametro $oCod


        $oRes=$oCon->query($cSQL, PDO::FETCH_ASSOC)->fetchAll();
        echo '<table>
                <thead>
                    <tr>
                        <th> Livros </th>
                    </tr>
                </thead>
            <tbody>';
        foreach($oRes as $oReg => $oLinha)
        {
            // Mesmo esquema, dois foreachs
            echo '<tr>';
            foreach($oLinha as $oCampo => $oValor){
                
                echo('<td>'.$oValor.'</td>');
                
            }
            echo '</tr>';
        }
        echo '
                    </tbody>
                </table>';
        return json_encode($oRes);
    }
    fnLivrosParecidos(2);
    echo '<br>';


    // ESSEAQUI é PUNK
    function fnRelatorioEmprestimo(string $oUs)
    {
        global $oCon;
        
        // Crio um vetor pra guardar os nomes do parametro $oUs ('$oUsuario')
        $array=[];


        // Testo pra saber se alguem digitou uma virgula
        if(str_contains($oUs,',')==true)
        {
            // 'explodo o vetor' (separa pelas ocorencias de ',' a string do segundo parametro, e joga no vetor que criei)
            // EU POSSO COLOCAR PRA ELE SEPARAR POR ' ' (espaco), mas nao coloquei, futuramente posso 
            $array = explode(",", $oUs);
        }

        // crio OUTRA variavel para guardar os codigos agora
        $ArrCod=[];

        // Deixo o texto pronto pra concatenar ele depois
        $cSQL="SELECT usuario.codigo codigo FROM usuario WHERE ";

        // foreach pra concatenar dependendo do numero de valores no vetor
        foreach($array as $indice=>$nome)
        {
            // Testo pra saber se é o primeiro indice, caso sim ele adiciona a condicional sem o 'OR'
            if($indice==0){
                $cSQL=$cSQL."usuario.nome LIKE '%".trim($nome)."%' ";
            }else
                $cSQL=$cSQL."OR usuario.nome LIKE '%".trim($nome)."%' ";
                //  Senão ele concatena com a mesma restrição porem com o 'OR'
        }
        $oQuery=$oCon->query($cSQL, PDO::FETCH_NUM)->fetchAll();
        // EXECUTO A CONSULTA
        // Decidi fazer dessa forma pq eu tentei fazer em único foreach, 
        // onde ele ia fazer a consulta pra pegar o codigo de cada nome, um por vez
        // porém ele não tava trazendo o codigo da segundo e subsquentes consultas, ele so executava a consulta uma vez
        // ENTÃO, decidi fazer assim.


        // Foreach com a consulta
            foreach($oQuery as $indice=>$codigo)
            {
                // outro foreach
                foreach($codigo as $valor){

                    // coloco o valor dos codigos nesse array
                    // eu podia sim usar apenas um array, no caso reutilizar o antigo
                    $ArrCod[]=$valor;
                }    
            }
            echo '<br>';

        echo '<table>
                    <thead>
                        <tr>
                            <th> Usuario </th>
                            <th> Em Atraso </th>
                            <th> No Prazo </th>
                        </tr>
                    </thead>
                <tbody>';
        
        // Agora neste foreach eu trago enfim a pessoa e as porcentagens
        // utilizando o vetor com cada codigo de usuario 
        foreach($ArrCod as $indice=>$valor){
            // Mais uma variavel desnecessaria
            $oCod=$valor;
            $cSQL="SELECT tbl.nome, IFNULL(CONCAT(CAST((atraso/tbl.total)*100 AS UNSIGNED), '%'), '0%') 'em atraso', IFNULL(CONCAT(CAST((tbl.prazo/tbl.total)*100 AS UNSIGNED), '%'), '0%') 'no prazo'
            FROM (
                SELECT usuario.nome, COUNT(devolvido) total, atraso.atraso, prazo.prazo
                FROM emprestimo
                LEFT JOIN
                (SELECT emprestimo.usuario, COUNT(devolvido)atraso FROM emprestimo WHERE devolvido>datafim AND emprestimo.usuario=$oCod) AS atraso
                ON emprestimo.usuario=atraso.usuario
                LEFT JOIN
                (SELECT emprestimo.usuario, COUNT(devolvido)prazo FROM emprestimo WHERE devolvido<datafim AND emprestimo.usuario=$oCod) AS prazo
                ON emprestimo.usuario=prazo.usuario
                LEFT JOIN usuario
                ON usuario.codigo=emprestimo.usuario
                WHERE emprestimo.usuario=$oCod
            ) as tbl;";


            $oRes=$oCon->query($cSQL, PDO::FETCH_NUM)->fetchAll();

            //Variavel para saber se a consulta traz registros vazios, eu sei que nao era pra acontecer isso...
            //porem eu tava com pressa, e essa foi a maneira mais rapida de resolver o problema 
            $show = true;
            // foreach
            foreach($oRes as $oReg => $oLinha)
            {
                echo '<tr>';
                // pra cada registro eu defino que ela é true, pra resetar ao padrão 
                $show=true;
                foreach($oLinha as $oCampo => $oValor){
                    // Testo se o nome (que por conveniencia é o primeiro valor) é vazio
                    if($oValor == '')
                        // se sim deixa a variavel como false
                        $show=false;


                        // testo se a variavel é true, ou seja, se o registro tem valor
                    if($show==true)
                        // se sim eu mostro o campo
                    echo('<td>'.$oValor.'</td>');
                    
                }
                echo '</tr>';
            }
        }
        echo '
                    </tbody>
                </table>';
        // deixo esta variavel global pra reutilizar ela nesse escopo
        global $oRes;
        return json_encode($oRes);
    }
    // Parametro separado por virgula
    fnRelatorioEmprestimo('Keanu, J');




    echo '<br><br>';

    // Cansei de comentar

    function fnPesquisa(string $texto)
    {
        global $oCon;
        $cSQL="SELECT acervo.codigo 'codigo', acervo.nome 'acervo', autor.nome 'autor', editora.nome 'editora' FROM acervo 
                LEFT JOIN autor ON autor.codigo = acervo.autor
                LEFT JOIN editora ON editora.codigo = acervo.editora 
                WHERE acervo.nome LIKE '%$texto%' OR autor.nome LIKE '%$texto%' OR editora.nome LIKE '%$texto%'";

        $oRes=$oCon->query($cSQL, PDO::FETCH_ASSOC)->fetchAll();
        echo '<table>
                <thead>
                    <tr>
                        <th> Codigo </th>
                        <th> Livro </th>
                        <th> Autor </th>
                        <th> Editora </th>
                    </tr>
                </thead>
            <tbody>';
        foreach($oRes as $oReg => $oLinha)
        {
            echo '<tr>';
            foreach($oLinha as $oCampo){
                
                echo('<td>'.$oCampo.'</td>');
          
            }
            echo '</tr>';
        }
        echo '
                    </tbody>
                </table>';

        return json_encode($oRes);
    }
    fnPesquisa('Não');


    echo '<br><br>';


    function fnImprestados()
    {
        global $oCon;
        $cSQL="SELECT
            acervo.nome, autor.nome, editora.nome, COUNT(emprestimo.datainicio) quant 
            FROM acervo
            INNER JOIN autor ON autor.codigo = acervo.autor
            INNER JOIN editora ON acervo.editora = editora.codigo
            INNER JOIN emprestimo ON acervo.codigo = emprestimo.acervo
            GROUP BY acervo.nome, autor.nome, editora.nome
            ORDER BY quant desc";

        $oRes=$oCon->query($cSQL, PDO::FETCH_NUM)->fetchAll();
        echo '<table>
                <thead>
                    <tr>
                        <th> Livro </th>
                        <th> Autor </th>
                        <th> Editora </th>
                        <th> Quantidade </th>
                    </tr>
                </thead>
            <tbody>';
        foreach($oRes as $oReg => $oLinha)
        {
            echo '<tr>';
            foreach($oLinha as $oCampo){
                    echo('<td>'.$oCampo.'</td>');
          
            }
            echo '</tr>';
        }
        echo '
                    </tbody>
                </table>';

        return json_encode($oRes);
    }
    fnImprestados();
?>