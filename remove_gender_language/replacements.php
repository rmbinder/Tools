<?php
/**
 ***********************************************************************************************
 * Replacements for the admidio plugin remove_gender_language
 *
 * 
 * The array is structured as follows:
 * $replacements = array(
 * 'search text1'       =>  'replace text1',
 * 'search text2'       =>  'replace text2',
 * ....
 * 'search textx'       =>  'replace textx')
 * 
 * Any "search text" found will be replaced by the "replace text".
 * 
 * 
 * A language expression can also be entered as search text, e.g. SYS_ADMINISTRATOR.
 * In this case, the entire expression is replaced.
 *
 *
 * Example:
 *
 * $replacements = array(
 * 'Administrierende'  =>  'Administratoren',
 * 'Empfänger:in'      =>  'Empfänger',
 * 'SYS_ALSO_VISITORS' =>  'auch Besucher')
 *
 ***********************************************************************************************
 */

$replacements = array(
'DAT_PARTICIPANTS_LIMIT'                            => 'Teilnehmerbegrenzung',
'INS_DATA_OF_ADMINISTRATOR'                         => 'Daten des Administrators',
'INS_DESCRIPTION_ADMINISTRATOR'                     => 'Gruppe der Administratoren des Systems',
'SYS_CONFIGURATION_ALL_USERS'                       => 'Konfiguration allen Benutzern zur Verfügung stellen',
'SYS_USER_VALID_LOGIN'                              => 'Diese Benutzer besitzt schon ein gültiges Login.',
'einer Administratorin oder einem Administrator'    => 'einem Administrator',
'einer Administratorin oder eines Administrators'   => 'eines Administrators',
'Die Administratorin oder der Administrator'        => 'Der Administrator',
'Administratorinnen und Administratoren'            => 'Administratoren',
'zu einer administrierenden Rolle'                  => 'zum Administrator',
'zu einer administrierende Rolle'                   => 'zum Administrator',
'eines Administrierenden'                           => 'eines Administrators',
'Die Administrienden'                               => 'Die Administratoren',
'Administrierende'                                  => 'Administratoren',
'zur/zum Administrator:in'                          => 'zum Administrator',
'eine:n Administrator:in'                           => 'den Administrator',
'Administrator:in'                                  => 'Administrator',
'die ausgewählte Empfängerin bzw. den ausgewählten Empfänger'                       => 'den ausgewählten Empfänger',
'der Empfängerin oder dem Empfänger'                => 'dem Empfänger',
'individuelle empfangende Person'                   => 'individueller Empfänger',
'Empfänger:innen'                                   => 'Empfänger',
'Empfänger:in'                                      => 'Empfänger',
'Empfangenden'                                      => 'Empfänger',
'Empfangende'                                       => 'Empfänger',
'Hier kann die Anzahl der Teilnehmenden'            =>  'Hier kann die Teilnehmeranzahl',
'Gäste und Mitglieder'                              => 'Besucher und Mitglieder',
'die Erstellerin bzw. der Ersteller zusammen mit der Benutzerin bzw. der Benutzer'  => 'der Ersteller und der Benutzer',
'keine eindeutige Benutzerin bzw. kein eindeutiger Benutzer'                        => 'kein eindeutiger Benutzer',
'eine neu registrierte Benutzerin oder ein neu registrierter Benutzer'              => 'ein neu registrierter Benutzer',
'die neue Benutzerin bzw. der neue Benutzer'                                        => 'der neue Benutzer',
'der angemeldeten Benutzerin bzw. des angemeldeten Benutzers'                       => 'des angemeldeten Benutzers',
'der angezeigten Benutzerin bzw. des angezeigten Benutzers'                         => 'des angezeigten Benutzers',
'Benutzern und Benutzerinnen'                       => 'Benutzern',
'Benutzerinnen und Benutzern'                       => 'Benutzern',
'jeweilige Benutzerin oder der jeweilige Benutzer'  => 'der jeweilige Benutzer',
'eine neue Benutzerin oder ein neuer Benutzer'      => 'ein neuer Benutzer',
'dieser Benutzerin bzw. dieses Benutzers'           => 'dieses Benutzers',
'von einer Benutzerin oder einem Benutzer'          => 'von einem Benutzer',
'von der Benutzerin oder dem Benutzer'              => 'vom Benutzer',
'dieser Benutzerin bzw. diesem Benutzer'            => 'diesem Benutzer',
'der Benutzerin bzw. des Benutzers'                 => 'des Benutzers',
'der Benutzerin oder des Benutzers'                 => 'des Benutzers',
'die Benutzerin bzw. den Benutzer'                  => 'den Benutzer',
'Der Benutzerin bzw. dem Benutzer'                  => 'Dem Benutzer',
'der Benutzerin bzw. dem Benutzer'                  => 'dem Benutzer',
'die Benutzerin oder der Benutzer'                  => 'der Benutzer',
'die Benutzerin bzw. der Benutzer'                  => 'der Benutzer',
'Die Benutzerin bzw. der Benutzer'                  => 'Der Benutzer',
'Die Benutzerin oder der Benutzer'                  => 'Der Benutzer',
'zwischen Benutzer:innen'                           => 'zwischen Benutzern',
'können von Benutzer:innen'                         => 'können von Benutzern',
'Der/Die aktuelle Benutzer:in'                      => 'Der aktuelle Benutzer',
'ein:e inaktive:r Benutzer:in'                      => 'ein inaktiver Benutzer',
'Neue:n Benutzer:in anlegen'                        => 'Neuen Benutzer anlegen',
'ein:e neue:r Benutzer'                             => 'ein neuer Benutzer',
'jede:n Benutzer'                                   => 'jeden Benutzer',
'kein:e Benutzer:in'                                => 'kein Benutzer',
'Diese:r Benutzer:in'                               => 'Dieser Benutzer',
'ein:e Benutzer:in'                                 => 'ein Benutzer',
'Benutzer:innen'                                    => 'Benutzer',
'Benutzer:in'                                       => 'Benutzer',
'zu Leiterinnen oder Leitern'                       => 'zu Leitern',
'Leiter:innen'                                      => 'Leiter',
'Leiter:in'                                         => 'Leiter',
'einer/einem Ehemaligen'                            => 'einem Ehemaligen',
'Absagen von Teilnehmenden'                         => 'Absagen von Teilnehmern',
'Liste der Teilnehmer'                              => 'Teilnehmerliste',
'Liste aller Teilnehmer'                            => 'Teilnehmerliste',
'Liste aller Teilnehmenden'                         => 'Liste aller Teilnehmer',
'Liste der Teilnehmenden'                           => 'Liste der Teilnehmer',
'Du hast bei den Teilnehmenden'                     => 'Du hast bei den Teilnehmern',
'Teilnehmende'                                      => 'Teilnehmer',
'Teilnehmenden'                                     => 'Teilnehmer',
'einer Moderatorin oder einem Moderator'            => 'einem Moderator',
'diese:n'                                           => 'diesen',
'der Absenderin bzw. des Absenders'                 => 'des Absenders',
'Absender:in'                                       => 'Absender',
'Besucher:innen'                                    => 'Besucher',
'er/sie'                                            => 'er',
'Code in das Formularfeld ein'                      => 'Code, bzw. das Ergebnis der Rechenaufgabe in das Formularfeld ein');






