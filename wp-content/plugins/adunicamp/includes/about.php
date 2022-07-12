<div class="about-adunicamp">
    <h1 class="title">ADunicamp</h1>
    <p>é um plugin para gerenciamento do site da <b>ADunicamp</b>.</p>
    <ul>
        <li><a href="https://github.com/evertonmessias/adunicamp" target="_blank">Projeto</a></li>
        <li><a href="https://portfolio.evertonm.com/" target="_blank">Site do Desenvolvedor</a></li>
    </ul>
    <br> 
    <hr>
    <h2 class="title">Registro de Acessos</h2>
    <table class="access">
        <tr>
            <th>id</th>
            <th>IP</th>
            <th>Data</th>
        </tr>
        <?php
        try {
            $lista = list_access('*');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        foreach ($lista as $item) {        
            $data = explode("-",explode(" ",$item->time)[0]);
            $datahora = $data[2]."/".$data[1]."/".$data[0]." , ".explode(" ",$item->time)[1];
            echo "<tr><td>" . $item->id . "</td><td>" . $item->ipadress . "</td><td>" . $datahora . "</td></tr>";
        }
        ?>
    </table>
</div>