<table border="1" width="100%">
    <? if (!empty($header)): ?>
    <thead>
    <tr>
        <? foreach($header as $h): ?>
        <th><?= $h ?></th>
        <? endforeach; ?>
    </tr>
    </thead>
    <? endif; ?>

    <tbody>
    <? foreach($data as $row): ?>
    <tr>
        <? foreach($row as $val): ?>
        <td><?= $val ?></td>
        <? endforeach; ?>
    </tr>
    <? endforeach; ?>
    </tbody>
</table>