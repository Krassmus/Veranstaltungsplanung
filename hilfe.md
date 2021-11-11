# Benutzung des Veranstaltungsplaners

Die Oberfläche des Veranstaltungsplaners ist so gebaut worden, dass man damit sehr schnell Termine von Veranstaltungen in Stud.IP planen kann. Dabei will der Planer nach Möglichkeit keine Vorgaben machen, wie man dabei vorgehen soll. Die Idee des Planers ist: Viele Wege führen zum Ziel.

Mal will man sich alle Veranstaltungen eines Studiengangs anschauen, um sicher zu gehen, dass es keine Überschneidungen in einem Studiengang gibt. Mal will man sich alle Termine eines Gebäudes ansehen, um zu sehen, wann noch irgendeinem Raum des Gebäudes etwas frei ist. Das sind streng genommen unterschiedliche Ansichten, die man braucht. Der Veranstaltungsplaner versucht dennoch, alle diese Ansicht unter einen Hut zu bringen.

## Aufbau des Fensters

![Übersicht des Planers](https://raw.githubusercontent.com/Krassmus/Veranstaltungsplanung/master/assets/images/screenshot_overview.png)

Der Veranstaltungsplaner besteht ja zum Glück eigentlich nur aus einer Seite in Stud.IP. Diese hat eine Sidebar und einen Hauptbereich. In dem Hauptbereich sieht man eine kalendarische Ansicht (Wochenansicht oder eventuell Monatsansicht). In der Sidebar wendet man verschiedene Filter an. In der Kombination dieser Filter bekommt man eine oder mehrere Veranstaltungen und die Termine dazu sieht man dann in der Hauptansicht. Verändert man die Filter, so werden sofort die Termine in der Hauptansicht neu geladen.

So kann man zum Beispiel zuerst nach Semester filtern und dann nach einer Einrichtung. In der Kombination bekommt man alle Veranstaltungen, die in diesem Semester in der betreffenden Einrichtung angeboten werden. In dem Hauptfenster sieht man dann allerdings nicht alle diese Veranstaltungen, sondern alle Termin dieser Veranstaltungen in dem jeweiligen Zeitbereich.

Ganz oben in der Sidebar ist allerdings noch eine besondere Auswahl: Objekt-Typ. Damit stellt man ein, ob man nach Veranstaltungen filtern möchte, nach Personen oder nach Ressourcen wie Gebäuden oder Räumen. Wählt man statt des vorausgewählen Typs *Veranstaltungen* nun *Ressourcen* aus, so werden die Filter in der Sidebar ausgewechselt. Das ist logisch, denn schließlich muss man jetzt nach anderen Objekten filtern und hat dazu andere Filterangaben wie die Zahl der Sitzplätze. Räume haben Sitzplätze, Veranstaltungen nicht. Und auf der anderen Seite haben Räume keine Semester, dafür sind Veranstaltungen den Semestern zugeordnet.

Was auch immer Sie hier auswählen, der Veranstaltungsplaner merkt sich das. Auch wenn Sie sich abmelden und wieder anmelden, der Veranstaltungsplaner wird dieselben Objekt-Typen und Filter ausgewählt haben, wie bei Ihrem letzten Besuch.

## Planer konfigurieren

Von diesen vielen Filtermöglichkeiten werden Sie einige vermutlich nie verwenden müssen. Besonders wenn in Ihrem Stud.IP viele freie Datenfelder festgelegt worden sind, kann die Liste der Filter sehr lang werden. Einige davon stören Sie vielleicht. Daher kann man den Planer so konfigurieren, dass lediglich die Filtermöglichkeiten angezeigt werden, die Sie auch wirklich benutzen werden. Klicken Sie dazu in der Sidebar links auf den Punkt (ganz unten) *Planer konfigurieren*.

![Übersicht des Planers](https://raw.githubusercontent.com/Krassmus/Veranstaltungsplanung/master/assets/images/screenshot_config.png)

* **Orientierungslinien**: Dies sind eine oder zwei Linien, die in der Wochenansicht des Planers rot angezeigt werden. Falls man die Vorgabe hat, dass die Termine nie nach 16 Uhr oder vor 8 Uhr stattfinden sollen, kann man die Orientierungslinien auf diese Zeitpunkte setzen, und man sieht sofort im Planer, wenn man etwas aus dem Raster fällt. Genau so könnte man sich damit Mittagspausen und so weiter markieren.
* **Beginn und Ende des Tages**: Weniger ist manchmal mehr. Hier kann man definieren, dass sowieso nie der Zeitraum von 0 Uhr bis 6 Uhr und nie der Abend ab 20 Uhr angezeigt werden soll. Dann trägt man hier den Beginn und Ende des Tages entsprechend ein und schon ist der Tag übersichtlicher.
* **Tage verstecken**: Oft weiß man auch, dass man Samstage und Sonntage gar nicht erst berücksichtigen muss in der Planung. Dann kann man hier Samstag und Sonntag auswählen. Diese Tage werden im Planer gar nicht mehr angezeigt und die Anzeige ist wieder etwas übersichtlicher geworden.
* **Immer fragen, bevor ein Termin verschoben wird**: Im Hauptbereich kann man Termine per Drag & Drop verschieben. Ist diese Option hier an, so wird der Planer jedes Mal nachfragen, ob denn wirklich verschoben werden soll. Falls einem diese ständige Nachfragerei zu nervig vorkommen sollte, kann man diese Option auch ausschalten.
* **Filter für X**: In den nächsten drei Sektionen kann man einstellen, welche Filter man sehen will und welche nicht. Man kann natürlich jederzeit die Filter auch wieder einblenden, wenn ein paar Monate später doch der Studienbereichsbaum wichtig wird.
* **Terminfarben**: In der letzten Sektion kann man einstellen, wie die Termine eingefärbt werden sollen. Das ist nützlich für die Übersichtlichkeit. Die sogenannten Standardfarben sind relativ einfach: regelmäßige Termine werden grünlich, Einzeltermine werden bläulich angezeigt. Mehr Unterscheidungen gibt es nicht. Man kann die Farben nach Einrichtungen, Studienbereichen, den Planungsfarben aus dem Planungstool für regelmäßige Termine oder nach Datenfeldern einstellen. Dann wird es richtig bunt! Gleiche Farbe bedeutet dann, gleiche Einrichtung, gleicher Studienbereich und so weiter.

## Der Terminplaner

Der Hauptbereich des Planers ist die Wochen- bzw. Monatsansicht des Terminplaners. Hier sieht man die Termine, die zu den in der Sidebar gefilterten Objekten passen, auf einem Blick. Oben rechts kann man die Wochen bzw. Monate mit Pfeiltasten durchblättern. Daneben kann man ein spezielles Datum auswählen. Und oben links kann man zwischen der Wochenansicht und der Monatsansicht wechseln. Ganz oben neben der Hilfelasche kann man überdies über ein Icon auch in den Konsummodus wechseln. Die Sidebar und Hauptnavigation werden dann weggeblendet, und man hat mehr Platz für den Planer in seinem Fenster.

In dem eigentlichen Planer kann man mit Drag & Drop die bestehenden Termine verschieben. Man kann ebenso die Termine vergrößern oder verkleinern. Und wenn man mit der Maus einen freien Bereich auswählt, so kann man einen neuen Termin erstellen. Danach öffnet sich ein Fenster, das einen fragt, für welche Veranstaltung man denn einen neuen Termin anlegen will (diese Veranstaltung muss schon existieren und den eingestellten Filtern entsprechen - ganz neue Veranstaltungen anlegen kann man hier nicht).

Wenn man einen Termin zieht mit Drag & Drop, so werden im Hintergrund einige Flächen dunkel dargestellt. Diese Flächen sind Bereiche, in denen man den Termin nicht hinverschieben kann, ohne Konflikte zu erschaffen.

* Vielleicht hat ein Lehrender des Termins dort eine andere Veranstaltung.
* Vielleicht ist dort der Raum des Termins belegt.
* Vielleicht ist dort die Teilnehmergruppe der Veranstaltung schon mit einem anderen Termin beschäftigt.

## Tipps & Tricks

Wenn Sie mit dem Veranstaltungsplaner arbeiten, können ein paar nette Tricks Ihnen sicherlich helfen:

1. Richten Sie sich ein: Konfigurieren Sie Ihren Planer immer so, wie Sie ihn brauchen. Schmeißen Sie nutzlose Filter raus und konzentrieren Sie sich auf die Bedienelemente, die Sie wirklich dauerhaft brauchen.
2. Nutzen Sie mehrere Fenster: Was man manchmal vergisst, ist, dass man in Stud.IP auch in mehreren Fenstern arbeiten kann. Klicken Sie dazu mit der rechten Maustasten auf den Reiter "Planer" und wählen Sie "Link in neuem Fenster öffnen" aus (so heißt das in Firefox). Ziehen Sie das neue Fenster auf einen zweiten Bildschirm. In diesem zweiten Fenster können Sie komplett andere Filter anwenden, nach anderen Objekt-Typen filtern und so weiter. So hat man viel besseren Überblick darüber, wohin man einen Veranstaltungstermin verschieben kann, ohne in Konflikte zu geraten.
3. Arbeiten Sie zusammen mit anderen Admins: Haben Sie mehrere Admins, die dieselbe Aufgabe machen? Dann können Sie sich auch jederzeit austauschen. Sie müssen dazu nicht am Telefon erklären, welche Filter gesetzt sind. Sie können einfach die URL aus der Adresszeile kopieren. Der Veranstaltungsplaner aktualisiert diese Adresse andauernd, wenn Sie einen Filter ändern. Der hintere Teil der URL heißt dann beispielsweise `...plugins.php/veranstaltungsplanung/planer/index#object_type=courses&semester_id=eea65bd6009499ccfcb34a4a02514e13`. Wenn ein anderer Admin diese URL aufruft, sind dann dieselben Filter ausgewählt wie bei Ihnen und die Person sieht dann dieselben Veranstaltungen. So kann man sich leichter austauschen und besser erklären, wo vielleicht noch Konflikte sind.
