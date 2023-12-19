<?php
if (isset($_POST['mmh_submit'])) {

    $fromDate = isset($_POST['from_date']) ? $_POST['from_date'] : '';
    $toDate = isset($_POST['to_date']) ? $_POST['to_date'] : '';

    $date_regex = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/';

    if (!preg_match($date_regex, $fromDate) || !preg_match($date_regex, $toDate)) {
        echo "Invalid date format. Please use YYYY-MM-DD HH:mm.";
    } else {

        $api_url = '';
        $stock_handler = new StockHandler();
        $stock_handler->StockUpdate($fromDate, $toDate);
    }
}
?>

<div class="container">
    <h2>MMH Integration plugin</h2>
    <h4>Stock update</h4>
    <div class="row">
        <form method="post" action="" enctype="multipart/form-data">
            <!-- Add two date input fields with the required format -->
            <label for="from_date">From Date:</label>
            <input type="text" name="from_date" id="from_date" required pattern="\d{4}-\d{2}-\d{2} \d{2}:\d{2}" placeholder="YYYY-MM-DD HH:mm ex 2023-12-13 08:00">

            <label for="to_date">To Date:</label>
            <input type="text" name="to_date" id="to_date" required pattern="\d{4}-\d{2}-\d{2} \d{2}:\d{2}" placeholder="YYYY-MM-DD HH:mm">

            <input type="submit" name="mmh_submit" class="button button-primary" value="Update stock">
        </form>
    </div>
</div>