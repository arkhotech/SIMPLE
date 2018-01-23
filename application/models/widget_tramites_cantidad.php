<?php
require_once 'widget.php';

class WidgetTramitesCantidad extends Widget {

    public function display() {
        if (!$this->config) {
            $display = '<p>Widget requiere configuración</p>';
            return $display;
        }

        $datos = array();


        foreach($this->config->procesos as $proceso_id){
            $p=Doctrine::getTable('Proceso')->find($proceso_id);
            if($p){
                $conteo = Doctrine_Query::create()
                    ->from('Tramite t, t.Etapas e, e.DatosSeguimiento d, t.Proceso p ,p.Cuenta c')
                    ->where('c.id = ?', $this->cuenta_id)
                    ->andWhere('p.activo=1 AND (p.id = ? OR p.root = ?)', array($p->root, $p->root))
                    ->andWhere('t.pendiente=1')
                    ->having('COUNT(d.id) > 0 OR COUNT(e.id) > 1')  //Mostramos solo los que se han avanzado o tienen datos
                    ->groupBy('t.id')
                    ->count();

                $datos[$p->nombre]['pendientes'] = $conteo;
            }
        }

        
        foreach($this->config->procesos as $proceso_id){
            $p=Doctrine::getTable('Proceso')->find($proceso_id);
            if($p){
                $conteo = Doctrine_Query::create()
                    ->from('Tramite t, t.Etapas e, e.DatosSeguimiento d, t.Proceso p ,p.Cuenta c')
                    ->where('c.id = ?', $this->cuenta_id)
                    ->andWhere('p.activo=1 AND (p.id = ? OR p.root = ?)', array($p->root, $p->root))
                    ->andWhere('t.pendiente=0')
                    ->having('COUNT(d.id) > 0 OR COUNT(e.id) > 1')  //Mostramos solo los que se han avanzado o tienen datos
                    ->groupBy('t.id')
                    ->count();

                $datos[$p->nombre]['completados'] = $conteo;
            }
        }
        
        
        $categories_arr=array();
        $pendientes_arr=array();
        $completados_arr=array();
        foreach ($datos as $key => $val) {
            $categories_arr[] = $key;
            $pendientes_arr[] = isset($val['pendientes']) ? (int)$val['pendientes'] : 0;
            $completados_arr[] = isset($val['completados']) ? (int)$val['completados'] : 0;
        }
        $categories = json_encode($categories_arr);
        $pendientes = json_encode($pendientes_arr);
        $completados = json_encode($completados_arr);
        
        $display = '<div class="grafico"></div>';
        $display.='
        <script type="text/javascript">
            $(document).ready(function(){
                new Highcharts.Chart({
                    chart: {
                        renderTo: $(".widget[data-id=' . $this->id . '] .grafico")[0],
                        type: "column"
                    },
                    title: null,
                    yAxis: {
                        title: {
                            text: "Nº de Trámites"
                        },
                    },
                    xAxis: {
                        categories: ' . $categories . '
                    },
                    series: [{
                        name: "Pendientes",
                        data: ' . $pendientes . '
                        },
                        {
                        name: "Completados",
                        data: ' . $completados . '
                    }]
                });
            });
        </script>';


        return $display;
    }

    public function displayForm() {
        $procesos = $this->Cuenta->getProcesosActivos();//Procesos;
        
        $procesos_array=$this->config?$this->config->procesos:array();
        
        $display = '<label>Procesos a desplegar</label>';
        foreach ($procesos as $p)
            $display.='<label><input type="checkbox" name="config[procesos][]" value="' . $p->id . '" ' . (in_array($p->id, $procesos_array) ? 'checked' : '') . ' /> ' . $p->nombre . '</label>';

        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('config[procesos]', 'Procesos', 'required');
    }

}
