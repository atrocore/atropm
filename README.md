## What is AtroPM?
AtroPM is a simple, but powerful, configurable, open-source Project Management Software, inspired by Github and Gitlab, which is based on the [AtroCore](https://github.com/atrocore/atrocore) software platform. AtroPM (as well as AtroCore) is distributed under GPLv3 License and is free. It has a lot of features right out-of-the-box and is thus an excellent tool for managing any kind of project.

AtroPM is a single page application (SPA) with API-centric and service-oriented architecture (SOA). It has a flexible data model based on entities and relations of all kinds.  

![kanban-board](/_assets/atropm-kanban-board.png)

## Why have we developed our own solution?
Yes, there are many other open-source solutions on the market. But most of them are complicated and not flexible at all. If you are not happy with the processes,  structures and layouts you have to choose some other solution, but this still does not guarantee, that you will be happy. By using our solution, you can configure it exactly as you want. Moreover, you can implement any processes, regardless of the complexity of your projects.

## User features

### Project Groups
- Many Project Groups are possible
- Labels and Milestones can be assigned to a Project Group.

### Projects
- Many Projects are possible
- Use Milestones and Labels assigned to a Project or its Project Group.

### Milestones
- Can be assigned to a Project or Project Group and be thus valid for all projects of that Project Group
- Use Milestones as Sprints, if you have implemented SCRUM.

### Issues
- Can be assigned to any project
- Use the Kanban Board, with real-time actualization, just like Trello
- Issues are placed on the Kanban Board Lists accordingly to their deadlines, if no deadline is set the issue is set as the last item
- Use Story Points
- Use Markdown in Descriptions and Notes
- Mention other users so they will be notified
- Follow chosen Issues
- Archived Issues are not shown by default.

### Labels
- Can be assigned to a Project or Project Group
- Only labels assigned to a Project or its Project Group can be used for its Issues
- Will help you to implement any process or workflow you want.

### Teams
- Are created automatically during creation of a new Project Group or a Project
- You can create additional Teams
- More than one Team can be assigned to a Project Group or a Project, all Teams assigned to a Project Group get automatic access to all Projects of that Project Group.

### Ownership Information
- You can have both, the owner and assigned user for any entity on the system

and more…

## Admin features
- Configurable data model → Want to have sub-tasks? No problem, configure them as an additional entity
- Configurable layouts, include Kanban Board
- Dynamic field logic → You can hide, make read-only or require some fields if certain conditions are met
- Configurable roles, with access levels and permissions on the field level

and more…

![atropm-issue-panel](/_assets/atropm-issue-panel.png)

## What Are the Advantages of AtroPM?

- Many out-of-the-box features
- Free – 100% open source, licensed under GPLv3
- REST API
- Service-oriented architecture (SOA)
- Responsive and user-friendly UI
- Configurable (entities, relations, layouts, labels, navigation, dashboards, etc.)
- Includes advantages of [AtroCore](https://github.com/atrocore/atrocore).

## Technology

AtroCore and AtroPM use PHP7, backbone.js, some Symfony and Zend components and Composer.

## Integrations

AtroPM has a REST API and can be integrated with any third-party system.
Please, [ask](https://atrocore.com/contact), if you want to know more.

## Requirements

- Unix-based system. Ubuntu is recommended.
* PHP 7.4 (or above).
* MySQL 5.5.3 (or above) or PostgreSQL 14.9 (or above).

### Installation

The Installation Guide is available [here](https://github.com/atrocore/docs/blob/master/atrocore/admin-guide/installation.md).

## License

AtroPM is published under the GNU GPLv3 [license](https://www.gnu.org/licenses/gpl-3.0.en.html).

## Support

- Report a Bug - https://github.com/atrocore/atrocore/issues/new
- Ask the Community - https://github.com/atrocore/atrocore/discussions
- Сontact us - https://atrocore.com/contact
