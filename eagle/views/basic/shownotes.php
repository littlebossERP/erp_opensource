
<div class="shownotes">
    <ul>
    <?php foreach ($result as $res): ?>
        <li><span><?php echo $res->version;?> <?php echo date("Ymd",strtotime($res->release_date));?></span> <?php echo $res->content;?></li> 
     <?php endforeach;?>   
    </ul>
</div>