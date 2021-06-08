<div class="wrap">
    <h2><?php echo $this->title ?></h2>
    <?php if ( !empty( $this->transaction ) ) {
        
        $data = explode('&', $this->transaction);
        $array = Computop_Api::ctSplit($data);
        
        foreach ($response_message as $key => $value) {
            $color = 'red';
            if($key == 'OK')
            {
                $color = 'green';
            }
            
            echo "<p color=$color>";
                echo $key.' : '.$value['description'];
            echo "</p>";
        }
        
        foreach ($array as $key => $value) {
            if($key != 'PCNr')
            {
                echo "<p>";
                echo $key.' : '.$value;
                echo "</p>";
            }
        }
    } ?>

    <p>
        <a class="button" href="admin.php?page=computop_transactions"><?php echo __( 'Back to the transactions', 'computop' ); ?></a>
    </p>
</div>
