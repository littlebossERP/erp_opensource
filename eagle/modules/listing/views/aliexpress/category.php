<table class="iv-table">
	<thead>
		<tr>
			<th>cateid</th>
			<th>name_zh</th>
			<th>name_en</th>
			<th>pid</th>
			<th>level</th>
		</tr>
	</thead>
	<tbody>
		<?php  foreach($category as $k => $cate): ?>
			<tr>
				<td><?=$cate['cateid']?></td>
				<td><?=$cate['name_zh']?></td>
				<td><?=$cate['name_en']?></td>
				<td><?=$cate['pid']?></td>
				<td><?=$cate['level']?></td>
			</tr>
		<?php endforeach;?>
	</tbody>
</table>