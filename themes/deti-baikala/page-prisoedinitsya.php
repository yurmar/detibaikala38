<?php
/**
 * Template Name: Присоединиться — Наставник
 *
 * Страница с анкетой кандидата для программы «Наставник».
 */

get_header();

$sent = isset( $_GET['nastavnik_sent'] ) ? sanitize_key( $_GET['nastavnik_sent'] ) : '';
?>

<style>
/* ===== ФОРМА НАСТАВНИК ===== */
.nc-wrap{max-width:860px;margin:0 auto;padding:5rem 0 4rem}
.nc-wrap h1{font-size:clamp(1.6rem,3vw,2.4rem);margin-bottom:.5rem}
.nc-intro{color:var(--text-secondary);margin-bottom:2rem;font-size:.95rem}
.nc-notice{padding:1rem 1.25rem;border-radius:var(--radius-sm);margin-bottom:1.75rem;font-size:.95rem}
.nc-notice--success{background:rgba(0,200,100,.1);border:1px solid rgba(0,200,100,.3);color:#0a9}
.nc-notice--error{background:rgba(220,50,50,.08);border:1px solid rgba(220,50,50,.25);color:#c44}
.nc-section{background:var(--bg-card);border:1px solid var(--border-subtle);border-left:4px solid var(--accent);border-radius:var(--radius);padding:1.5rem 1.75rem;margin-bottom:1.5rem}
.nc-section h3{font-size:.8rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);margin-bottom:1.25rem;font-family:'Montserrat',sans-serif}
.nc-section .nc-note{font-size:.82rem;color:var(--text-secondary);margin-bottom:1rem}
.fields{display:grid;gap:1rem;margin-bottom:1rem}
.fields.three{grid-template-columns:repeat(3,1fr)}
.fields.two{grid-template-columns:repeat(2,1fr)}
.fields.one{grid-template-columns:1fr}
@media(max-width:640px){.fields.three,.fields.two{grid-template-columns:1fr}}
.field{display:flex;flex-direction:column}
.rline{display:flex;flex-direction:column;gap:.3rem;width:100%}
.rline label{font-size:.82rem;font-weight:500;color:var(--text-secondary);line-height:1.4}
.red{color:#e55}
.rfield,.nc-input{width:100%;padding:.55rem .75rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-primary);color:var(--text-primary);font-family:inherit;font-size:.9rem;transition:border-color var(--transition),box-shadow var(--transition)}
.rfield:focus,.nc-input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim)}
textarea.rfield,textarea.nc-input{min-height:90px;resize:vertical}
select.rfield,select.nc-input{cursor:pointer}
.rfield.rf_error{border-color:#e55!important;box-shadow:0 0 0 3px rgba(220,50,50,.12)!important}
.rfield_error{font-size:.75rem;color:#e55;visibility:hidden;line-height:1.3}
.citizenship-row{display:flex;align-items:center;gap:1.25rem;margin-top:.25rem;flex-wrap:wrap}
.citizenship-row label{font-size:.82rem;color:var(--text-secondary);margin-bottom:0}
.citizenship-row .radio-opt{display:flex;align-items:center;gap:.35rem;cursor:pointer;font-size:.9rem}
.citizenship-row .radio-opt input{width:16px;height:16px;accent-color:var(--accent)}
.birthday-selects{display:flex;gap:.5rem;flex-wrap:wrap}
.birthday-selects select{padding:.5rem .6rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-primary);color:var(--text-primary);font-size:.88rem;transition:border-color var(--transition)}
.birthday-selects select:focus{outline:none;border-color:var(--accent)}
/* Динамические блоки */
.nc-dynamic-entry{background:var(--bg-secondary);border:1px solid var(--border-subtle);border-radius:var(--radius-sm);padding:1rem 1.1rem;margin-bottom:.75rem}
.nc-dynamic-entry label{font-size:.82rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:.25rem}
.nc-dynamic-entry input[type=text],.nc-dynamic-entry select,.nc-dynamic-entry textarea{width:100%;padding:.5rem .7rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-primary);color:var(--text-primary);font-family:inherit;font-size:.88rem;margin-bottom:.65rem;transition:border-color var(--transition)}
.nc-dynamic-entry input[type=text]:focus,.nc-dynamic-entry select:focus,.nc-dynamic-entry textarea:focus{outline:none;border-color:var(--accent)}
.nc-dynamic-entry textarea{min-height:70px;resize:vertical}
.btn-add{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border:1.5px dashed var(--accent);border-radius:var(--radius-sm);background:var(--accent-dim);color:var(--accent);font-family:inherit;font-size:.82rem;font-weight:600;cursor:pointer;transition:all var(--transition)}
.btn-add:hover{background:var(--accent);color:#fff}
.btn-remove{display:inline-flex;align-items:center;padding:.3rem .8rem;border:1px solid var(--border-subtle);border-radius:var(--radius-sm);background:transparent;color:var(--text-muted);font-family:inherit;font-size:.78rem;cursor:pointer;transition:all var(--transition)}
.btn-remove:hover{border-color:#e55;color:#e55}
/* Согласия */
.nc-agreement{background:var(--bg-secondary);border:1px solid var(--border-subtle);border-radius:var(--radius);padding:1.25rem 1.5rem;margin-bottom:1rem}
.nc-agreement h4{font-size:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-primary);margin-bottom:.75rem;font-family:'Montserrat',sans-serif}
.nc-agreement ul{padding-left:1.25rem;margin:.5rem 0 .75rem}
.nc-agreement ul li{font-size:.85rem;color:var(--text-secondary);margin-bottom:.35rem}
.nc-agreement p{font-size:.85rem;color:var(--text-secondary);margin-bottom:.5rem}
.nc-agreement em{font-size:.82rem;color:var(--text-muted)}
.nc-check-row{display:flex;align-items:flex-start;gap:.75rem}
.rline.check-rline{display:flex;align-items:flex-start}
input[type=checkbox].rfield{flex-shrink:0;width:18px;height:18px;margin-top:.15rem;accent-color:var(--accent);cursor:pointer}
.nc-check-row label{font-size:.87rem;line-height:1.55;color:var(--text-secondary);cursor:pointer}
.nc-check-row label a{color:var(--accent);text-decoration:underline}
/* Кнопка отправить */
.nc-submit-row{text-align:center;margin-top:2rem}
.btnsubmit{min-width:220px;padding:.75rem 2.5rem;font-size:1rem;cursor:pointer}
.btnsubmit.disabled{opacity:.45;cursor:not-allowed}
</style>

<div class="container">
<div class="nc-wrap" id="nastavnik-form">

  <div class="section-label">Программа «Наставник»</div>
  <h1>Анкета кандидата</h1>
  <p class="nc-intro">Заполните анкету, чтобы присоединиться к программе. Поля, отмеченные <span class="red">*</span>, обязательны для заполнения.</p>

  <?php if ( 'success' === $sent ) : ?>
  <div class="nc-notice nc-notice--success">
    Ваша анкета успешно отправлена! Мы свяжемся с вами в ближайшее время.
  </div>
  <?php elseif ( 'error' === $sent ) : ?>
  <div class="nc-notice nc-notice--error">
    Произошла ошибка при отправке. Пожалуйста, заполните все обязательные поля и попробуйте снова.
  </div>
  <?php endif; ?>

  <?php if ( 'success' !== $sent ) : ?>

  <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="form_check" id="nc-form">
    <?php wp_nonce_field( 'db_nastavnik_form', 'db_nastavnik_nonce' ); ?>
    <input type="hidden" name="action" value="db_nastavnik_form">

    <!-- ===== 1. ОБЩИЕ СВЕДЕНИЯ ===== -->
    <div class="nc-section">
      <h3>1. Общие сведения</h3>

      <div class="fields three">
        <div class="field">
          <p class="rline">
            <label>Фамилия <span class="red">*</span></label>
            <input type="text" class="rfield" name="your-surname">
          </p>
        </div>
        <div class="field">
          <p class="rline">
            <label>Имя <span class="red">*</span></label>
            <input type="text" class="rfield" name="your-name">
          </p>
        </div>
        <div class="field">
          <p class="rline">
            <label>Отчество <span class="red">*</span></label>
            <input type="text" class="rfield" name="your-patronymic">
          </p>
        </div>
      </div>

      <div class="fields one">
        <div class="rline">
          <label>Населённый пункт, в котором проживаете постоянно, район населённого пункта</label>
          <input type="text" class="rfield" name="your-residential-address">
        </div>
      </div>

      <div class="fields three">
        <div class="field">
          <p class="rline">
            <label>Контактный телефон <span class="red">*</span></label>
            <input type="text" class="rfield" name="your-phone">
          </p>
        </div>
        <div class="field">
          <p class="rline">
            <label>Электронный адрес <span class="red">*</span></label>
            <input type="text" class="rfield" name="your-email">
          </p>
        </div>
        <div class="field">
          <p class="rline">
            <label>Дата рождения <span class="red">*</span></label>
            <span class="birthday-selects">
              <select name="your-birthday-day">
                <?php for ( $d = 1; $d <= 31; $d++ ) : ?>
                <option value="<?php echo esc_attr( $d ); ?>"><?php echo esc_html( $d ); ?></option>
                <?php endfor; ?>
              </select>
              <select name="your-birthday-month">
                <option value=""></option>
                <?php
                $months = array( 'Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня',
                                 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря' );
                foreach ( $months as $month ) :
                ?>
                <option value="<?php echo esc_attr( $month ); ?>"><?php echo esc_html( $month ); ?></option>
                <?php endforeach; ?>
              </select>
              <select name="your-birthday-year">
                <option value=""></option>
                <?php for ( $y = 1940; $y <= 2000; $y++ ) : ?>
                <option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
                <?php endfor; ?>
              </select>
            </span>
          </p>
        </div>
      </div>

      <div class="fields two">
        <div class="field">
          <p class="rline">
            <label>Вероисповедание <span class="red">*</span></label>
            <input type="text" class="rfield" name="your-worship">
          </p>
        </div>
      </div>

      <div class="citizenship-row">
        <label>Гражданство РФ <span class="red">*</span></label>
        <label class="radio-opt"><input type="radio" name="citizenship" value="Да" checked> Да</label>
        <label class="radio-opt"><input type="radio" name="citizenship" value="Нет"> Нет</label>
      </div>
    </div>

    <!-- ===== 2. СОСТОЯНИЕ ЗДОРОВЬЯ ===== -->
    <div class="nc-section">
      <h3>2. Состояние здоровья</h3>

      <div class="fields one">
        <p class="rline">
          <label>Как вы оцениваете своё состояние здоровья? <span class="red">*</span></label>
          <select class="rfield" name="health">
            <option value=""></option>
            <option value="отлично">отлично</option>
            <option value="хорошо">хорошо</option>
            <option value="средне">средне</option>
            <option value="плохо">плохо</option>
          </select>
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Имеете ли вы серьёзные заболевания? Если да, укажите, какие.</label>
          <input type="text" class="nc-input" name="your-disease">
        </p>
      </div>
    </div>

    <!-- ===== 3. ОБРАЗОВАНИЕ ===== -->
    <div class="nc-section">
      <h3>3. Образование</h3>
      <div id="education_container"></div>
      <button type="button" class="btn-add" onclick="ncAddEducation()">+ Добавить образование</button>
    </div>

    <!-- ===== 4. ТРУДОУСТРОЙСТВО ===== -->
    <div class="nc-section">
      <h3>4. Трудоустройство</h3>
      <p class="nc-note">Укажите место вашей работы на данный момент:</p>

      <div class="fields one">
        <p class="rline">
          <label>Название организации <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-organization">
        </p>
      </div>
      <div class="fields one">
        <p class="rline">
          <label>Должность <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-post">
        </p>
      </div>
      <div class="fields one">
        <p class="rline">
          <label>Исполняемые обязанности <span class="red">*</span></label>
          <textarea class="rfield" name="your-duty"></textarea>
        </p>
      </div>
      <div class="fields one">
        <p class="rline">
          <label>Укажите ваш текущий рабочий график <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-work-schedule">
        </p>
      </div>
    </div>

    <!-- ===== 5. ИНТЕРЕСЫ, ХОББИ ===== -->
    <div class="nc-section">
      <h3>5. Интересы, хобби</h3>
      <div class="fields one">
        <p class="rline">
          <label>Чем вы любите заниматься в свободное время? <span class="red">*</span></label>
          <textarea class="rfield" name="your-hobby"></textarea>
        </p>
      </div>
    </div>

    <!-- ===== 6. СЕМЕЙНОЕ ПОЛОЖЕНИЕ ===== -->
    <div class="nc-section">
      <h3>6. Семейное положение</h3>

      <div class="fields one">
        <p class="rline">
          <label>Семейное положение <span class="red">*</span></label>
          <select class="rfield" name="family">
            <option value=""></option>
            <option value="Женат (замужем)">Женат (замужем)</option>
            <option value="Гражданский брак">Гражданский брак</option>
            <option value="Разведён (разведена)">Разведён (разведена)</option>
            <option value="Вдовец (вдова)">Вдовец (вдова)</option>
            <option value="Не женат (не замужем)">Не женат (не замужем)</option>
          </select>
        </p>
      </div>

      <p class="nc-note">Пожалуйста, укажите членов вашей семьи:</p>
      <div id="family_container"></div>
      <button type="button" class="btn-add" onclick="ncAddFamily()">+ Добавить члена семьи</button>
    </div>

    <!-- ===== 7. ОПЫТ РАБОТЫ С ДЕТЬМИ ===== -->
    <div class="nc-section">
      <h3>7. Опыт работы с детьми</h3>
      <p class="nc-note">Пожалуйста, укажите ваш текущий и предыдущий опыт работы с детьми (оплачиваемая работа, волонтёрская деятельность и т.д.)</p>
      <div id="experience_container"></div>
      <button type="button" class="btn-add" onclick="ncAddExperience()">+ Добавить пункт</button>
    </div>

    <!-- ===== 8. ВОПРОСЫ ===== -->
    <div class="nc-section">
      <h3>8. Пожалуйста, как можно подробнее ответьте на следующие вопросы</h3>

      <div class="fields one">
        <p class="rline">
          <label>Вы хотите участвовать в Программе Наставник в качестве: <span class="red">*</span></label>
          <select class="rfield" name="your-property">
            <option value=""></option>
            <option value="Наставника">Наставника</option>
            <option value="Волонтера">Волонтера</option>
            <option value="Партнёра (оказывать единоразовую/постоянную финансовую поддержку)">Партнёра (оказывать единоразовую/постоянную финансовую поддержку)</option>
          </select>
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Почему вы хотите стать наставником (волонтером/партнёром)? <span class="red">*</span></label>
          <textarea class="rfield" name="your-why"></textarea>
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Какие качества вашего характера, особенные знания, навыки и т.д., могут, по вашему мнению, помочь становлению и развитию ребёнка-сироты? <span class="red">*</span></label>
          <textarea class="rfield" name="your-property-character"></textarea>
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Как бы вы описали (охарактеризовали) сами себя? <span class="red">*</span></label>
          <textarea class="rfield" name="your-characterized"></textarea>
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Почему вы хотите помогать ребёнку-сироте? <span class="red">*</span></label>
          <textarea class="rfield" name="your-help"></textarea>
        </p>
      </div>

      <p class="nc-note" style="margin-top:.5rem">Какие ваши ожидания от ребёнка, с которым вы хотите взаимодействовать?</p>

      <div class="fields two">
        <div class="field">
          <p class="rline">
            <label>Возраст <span class="red">*</span></label>
            <input type="text" class="rfield" name="your-expectations_age">
          </p>
        </div>
        <div class="field">
          <p class="rline">
            <label>Пол <span class="red">*</span></label>
            <select class="rfield" name="your-expectations_gender">
              <option value=""></option>
              <option value="Мужской">Мужской</option>
              <option value="Женский">Женский</option>
              <option value="Не имеет значения">Не имеет значения</option>
            </select>
          </p>
        </div>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Насколько важным для вас является характер ребёнка (внешний вид, поведенческие особенности)? <span class="red">*</span></label>
          <textarea class="rfield" name="your-nature"></textarea>
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Как часто вы сможете навещать ребенка в учреждении? <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-visit">
        </p>
      </div>

      <div class="citizenship-row">
        <label>Согласны ли вы взаимодействовать с ребёнком, имеющим функциональные ограничения или особенные потребности? <span class="red">*</span></label>
        <label class="radio-opt"><input type="radio" name="interaction" value="Да" checked> Да</label>
        <label class="radio-opt"><input type="radio" name="interaction" value="Нет"> Нет</label>
      </div>
    </div>

    <!-- ===== 9. БЕЗОПАСНОСТЬ ===== -->
    <div class="nc-section">
      <h3>9. В целях соблюдения прав и интересов ребёнка</h3>

      <div class="fields one">
        <p class="rline">
          <label>Употребляете ли вы алкоголь? Если да, то как часто? <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-alcohol">
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Употребляете ли вы табачные изделия? <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-tobacco">
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Употребляете ли вы психотропные вещества? Если да, укажите какие. <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-psychotropic">
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Употребляли ли вы наркотики? Если да, укажите, какие, и как давно. <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-narcotic">
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Были ли вы осуждены за преступления, связанные с унижением человеческого достоинства, вовлечением детей в незаконную деятельность, жестоким обращением с детьми, сексуальными посягательствами любого рода, пренебрежением ребёнка и т.д.? Если да, пожалуйста, объясните. <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-convicted">
        </p>
      </div>

      <div class="fields one">
        <p class="rline">
          <label>Были ли вы ранее лишены родительских или опекунских прав? Если да, пожалуйста, конкретизируйте. <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-deprivation">
        </p>
      </div>

      <div class="citizenship-row">
        <label>Согласны ли вы по необходимости заполнять отчёты о проделанной совместно с ребёнком деятельности (1 раз в неделю)? <span class="red">*</span></label>
        <label class="radio-opt"><input type="radio" name="report" value="Да" checked> Да</label>
        <label class="radio-opt"><input type="radio" name="report" value="Нет"> Нет</label>
      </div>

      <div class="fields one" style="margin-top:1rem">
        <p class="rline">
          <label>Откуда вы получили информацию о Программе Наставник и необходимости помощи детям-сиротам? <span class="red">*</span></label>
          <input type="text" class="rfield" name="your-get">
        </p>
      </div>
    </div>

    <!-- ===== СОГЛАСИЯ ===== -->
    <div class="nc-section">

      <div class="nc-agreement">
        <h4>Личное заявление</h4>
        <div class="nc-check-row">
          <p class="rline check-rline">
            <input type="checkbox" class="rfield" name="agreement" id="agreement" value="1">
          </p>
          <label for="agreement">
            Я понимаю цели Программы Наставник и обязуюсь следовать разработанным индивидуальным планам, предложенным мне координатором
            (с учётом оценки потребностей детей-сирот), а также соблюдать конфиденциальность по любой личной информации о ребёнке, которая может стать мне известной.
          </label>
        </div>
        <ul>
          <li>я обязуюсь сообщать координатору Программы об изменениях в моём графике (при невозможности присутствовать на запланированных ранее мероприятиях) или состоянии моего здоровья.</li>
          <li>я обязуюсь не покидать территорию учреждения вместе с ребёнком, без ведома и разрешения дирекции и координатора.</li>
          <li>я обязуюсь действовать исключительно в интересах ребёнка, способствуя его всестороннему развитию, получению образования и подготовке к самостоятельной жизни.</li>
        </ul>
        <p>Я подтверждаю, что вышеуказанная информация является правдивой и полной. Также даю согласие на проверку данных, указанных в данной анкете.</p>
        <p><em>Проект Наставник обязуется соблюдать конфиденциальность предоставленной волонтёром информации.</em></p>
      </div>

      <div class="nc-agreement">
        <h4>Согласие на обработку персональных данных</h4>
        <div class="nc-check-row">
          <p class="rline check-rline">
            <input type="checkbox" class="rfield" name="personal_data" id="personal_data" value="1">
          </p>
          <label for="personal_data">
            Я даю согласие на обработку своих персональных данных, то есть совершение, в том числе, следующих действий:
            обработку (включая сбор, систематизацию, накопление, хранение, уточнение (обновление, изменение),
            использование, обезличивание, блокирование, уничтожение персональных данных), при этом общее описание
            вышеуказанных способов обработки данных приведено в Федеральном законе от 27.07.2006 № 152-ФЗ,
            а также на передачу такой информации третьим лицам, в случаях, установленных нормативными документами
            вышестоящих органов и законодательством.
          </label>
        </div>
      </div>

    </div>

    <div class="nc-submit-row">
      <input type="submit" value="Отправить" class="btn btn-primary btnsubmit">
    </div>

  </form>
  <?php endif; ?>

</div><!-- .nc-wrap -->
</div><!-- .container -->

<script>
/* ===== ДИНАМИЧЕСКИЕ БЛОКИ ===== */
var ncEduCount = 0, ncFamCount = 0, ncExpCount = 0;

function ncAddEducation() {
  ncEduCount++;
  var n = ncEduCount;
  var html =
    '<div class="nc-dynamic-entry" id="nc_edu_' + n + '">' +
      '<label>Образование</label>' +
      '<select name="education[' + n + '][title]">' +
        '<option value=""></option>' +
        '<option value="Общеобразовательная школа">Общеобразовательная школа</option>' +
        '<option value="Университет, институт, техникум">Университет, институт, техникум</option>' +
        '<option value="Дополнительные курсы, тренинги, семинары">Дополнительные курсы, тренинги, семинары</option>' +
      '</select>' +
      '<label>Учебное заведение</label>' +
      '<input type="text" name="education[' + n + '][institution]">' +
      '<label>Полученная специальность, степени или другие квалификации</label>' +
      '<input type="text" name="education[' + n + '][specialty]">' +
      '<button type="button" class="btn-remove" onclick="document.getElementById(\'nc_edu_' + n + '\').remove()">Удалить</button>' +
    '</div>';
  document.getElementById('education_container').insertAdjacentHTML('beforeend', html);
}

function ncAddFamily() {
  ncFamCount++;
  var n = ncFamCount;
  var html =
    '<div class="nc-dynamic-entry" id="nc_fam_' + n + '">' +
      '<label>Имя</label>' +
      '<input type="text" name="familyname[' + n + '][name]">' +
      '<label>Пол</label>' +
      '<select name="familyname[' + n + '][gender]">' +
        '<option value=""></option>' +
        '<option value="Мужской">Мужской</option>' +
        '<option value="Женский">Женский</option>' +
      '</select>' +
      '<label>Возраст</label>' +
      '<input type="text" name="familyname[' + n + '][age]">' +
      '<label>Кем приходится</label>' +
      '<input type="text" name="familyname[' + n + '][status]">' +
      '<button type="button" class="btn-remove" onclick="document.getElementById(\'nc_fam_' + n + '\').remove()">Удалить</button>' +
    '</div>';
  document.getElementById('family_container').insertAdjacentHTML('beforeend', html);
}

function ncAddExperience() {
  ncExpCount++;
  var n = ncExpCount;
  var html =
    '<div class="nc-dynamic-entry" id="nc_exp_' + n + '">' +
      '<label>Название организации</label>' +
      '<input type="text" name="experiencetitle[' + n + '][title]">' +
      '<label>Должность</label>' +
      '<input type="text" name="experiencetitle[' + n + '][post]">' +
      '<label>Исполняемые обязанности</label>' +
      '<textarea name="experiencetitle[' + n + '][duty]"></textarea>' +
      '<label>Возрастная группа детей</label>' +
      '<input type="text" name="experiencetitle[' + n + '][age]">' +
      '<button type="button" class="btn-remove" onclick="document.getElementById(\'nc_exp_' + n + '\').remove()">Удалить</button>' +
    '</div>';
  document.getElementById('experience_container').insertAdjacentHTML('beforeend', html);
}

/* Добавляем по одному блоку при загрузке */
document.addEventListener('DOMContentLoaded', function() {
  ncAddEducation();
  ncAddFamily();
  ncAddExperience();
});

/* ===== ВАЛИДАЦИЯ ===== */
(function($) {
  $(function() {
    var form = $('.form_check');
    if (!form.length) return;

    var btn = form.find('.btnsubmit');

    /* Добавляем тексты ошибок и помечаем поля пустыми */
    form.find('.rfield').addClass('empty_field').parents('.rline').append('<span class="rfield_error">Заполните это поле</span>');
    btn.addClass('disabled');

    function checkInput() {
      form.find('.rfield').each(function() {
        var $el = $(this);
        if ($el.is(':checkbox')) {
          $el.toggleClass('empty_field', !$el.is(':checked'));
        } else {
          $el.toggleClass('empty_field', $el.val() === '');
        }
      });
    }

    setInterval(function() {
      checkInput();
      var hasEmpty = form.find('.empty_field').length > 0;
      btn.toggleClass('disabled', hasEmpty);
    }, 500);

    btn.on('click', function(e) {
      if ($(this).hasClass('disabled')) {
        e.preventDefault();
        form.find('.empty_field').addClass('rf_error');
        form.find('.empty_field').parents('.rline').find('.rfield_error').css({ visibility: 'visible' });
      }
    });
  });
})(jQuery);
</script>

<?php get_footer(); ?>
