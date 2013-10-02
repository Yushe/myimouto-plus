<h2>Routes</h2>

<table class="table table-striped">
  <thead>
    <th>Prefix</th>
    <th>Verb</th>
    <th>URI Pattern</th>
    <th>Controller#Action</th>
  </thead>
  <tbody style="font-family:Courier New, sans-serif;">
    <?php foreach ($this->routes as $route) : ?>
    <tr>
      <td style="text-align:right;"><?= $route[0] ?></td>
      <td><?= $route[1] ?></td>
      <td><?= $route[2] ?></td>
      <td><?= $route[3] ?></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
