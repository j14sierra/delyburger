<div class="container-fluid py-3 p-lg-4">

  <div class="row">

    <!--==============================
          Breadcrumb
         ================================-->

    <div class="col-12 mb-3 position-relative">

      <div class="d-lg-flex justify-content-lg-between mt-2">

        <div class="text-capitalize h5 ps-2">Pruebas Unitarias</div>

        <div class="pe-0">
          <ul class="nav justify-content-lg-end">
            <li class="nav-item">
              <a class="nav-link py-0 px-0 text-dark" href="/">Inicio</a>
            </li>
            <li class="nav-item ps-3">/</li>
            <li class="nav-item">
              <a class="nav-link py-0 disabled text-capitalize" href="#">Pruebas Unitarias</a>
            </li>
          </ul>
        </div>

      </div>

    </div>

    <!--==============================
          Módulos
         ================================-->


    <div class="container mt-4">

      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">Resultados de Pruebas Unitarias</h4>
        </div>

        <div class="card-body">
          <p>Este módulo muestra el resultado de las pruebas ejecutadas en PHPUnit.</p>
          <p>Última ejecución:
            <strong><?php echo date("Y-m-d H:i:s", filemtime("reports/unit-report.html")); ?></strong>
          </p>

          <hr>

          <h5>Historial de Ejecuciones</h5>

          <div class="accordion" id="historyAccordion">

            <?php
            $files = glob("reports/history/*.html");
            rsort($files); // Los más recientes primero

            $index = 0;
            foreach ($files as $file):

              $name = basename($file);
              $date = date("Y-m-d H:i:s", filemtime($file));
              $accordionId = "report_" . $index;
            ?>

              <div class="accordion-item">
                <h2 class="accordion-header" id="heading_<?php echo $index; ?>">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#<?php echo $accordionId; ?>" aria-expanded="false"
                    aria-controls="<?php echo $accordionId; ?>">
                    <strong><?php echo $date; ?></strong>
                  </button>
                </h2>

                <div id="<?php echo $accordionId; ?>" class="accordion-collapse collapse"
                  aria-labelledby="heading_<?php echo $index; ?>" data-bs-parent="#historyAccordion">

                  <div class="accordion-body">

                    <iframe src="<?php echo $file; ?>" style="width:100%; height:600px; border:none;"
                      class="shadow-sm rounded"></iframe>

                  </div>
                </div>
              </div>

            <?php
              $index++;
            endforeach;
            ?>

          </div>
        </div>
      </div>

    </div>


  </div>

</div>