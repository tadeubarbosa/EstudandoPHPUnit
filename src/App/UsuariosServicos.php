<?php

namespace App;

/**
 * Description of UsuariosServicos
 *
 * @author Tadeu
 */
class UsuariosServicos extends Persistence\ServicosBase
{

    protected $table = 'usuarios';

    /** que não seja id * */
    protected $collumUnique = 'email';

}
